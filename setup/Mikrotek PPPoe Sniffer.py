#!/usr/bin/env python
"""
MERGED MIKROTIK IDS - API + NetFlow Collector
Gets client list from API and REAL flow data from NetFlow
"""

import traceback
import sys


def _pause_on_error(exc_type, exc, tb):
    traceback.print_exception(exc_type, exc, tb)
    try:
        input("\nPress Enter to close...")
    except EOFError:
        pass


sys.excepthook = _pause_on_error

import os
import socket
import struct
import json
import time
import math
import sys
from datetime import datetime
from collections import defaultdict, deque
import threading
import requests
import logging

# =========================
# DEPENDENCIES
# =========================
try:
    import routeros_api
except ImportError:
    print("Missing dependencies. Install with:")
    print("pip install requests python-dotenv routeros-api")
    sys.exit(1)

try:
    from dotenv import load_dotenv
except ImportError:
    print("Install python-dotenv: pip install python-dotenv")
    sys.exit(1)

# Load environment variables
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(BASE_DIR, ".env"))

# =========================
# CONFIGURATION
# =========================
MIKROTIK_HOST = os.getenv("MIKROTIK_HOST", "192.168.96.1")
MIKROTIK_USER = os.getenv("MIKROTIK_USER", "admin")
MIKROTIK_PASSWORD = os.getenv("MIKROTIK_PASSWORD", "Makego0d")
MIKROTIK_PORT = int(os.getenv("MIKROTIK_PORT", "8728"))

API_URL = os.getenv("API_URL", "https://ids-ensemble-api.onrender.com/api/analyze")

# NetFlow configuration
NETFLOW_PORT = 2055
ANALYSIS_INTERVAL = 60  # Analyze every 60 seconds
MIN_FLOWS_FOR_ANALYSIS = 10  # Minimum flows needed for analysis

# =========================
# LOGGING SETUP
# =========================
LOG_DIR = os.path.join(BASE_DIR, "logs")
os.makedirs(LOG_DIR, exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(os.path.join(LOG_DIR, "merged_ids.log"), encoding="utf-8"),
        logging.StreamHandler(),
    ],
)
logger = logging.getLogger(__name__)

# =========================
# UTILITY FUNCTIONS
# =========================
def is_client_ip(ip: str) -> bool:
    """Monitor ALL IPs except known ISP/infrastructure networks."""
    if not ip or ip == "0.0.0.0" or ip == "255.255.255.255":
        return False
    
    ignore_networks = [
        "190.168", "191.168", "192.168", "193.168",
        "100.64.", "100.65.", "100.66.", "100.67.", "100.68.", "100.69.",
        "100.70.", "100.71.", "100.72.", "100.73.", "100.74.", "100.75.",
        "100.76.", "100.77.", "100.78.", "100.79.", "100.80.", "100.81.",
        "100.82.", "100.83.", "100.84.", "100.85.", "100.86.", "100.87.",
        "100.88.", "100.89.", "100.90.", "100.91.", "100.92.", "100.93.",
        "100.94.", "100.95.", "100.96.", "100.97.", "100.98.", "100.99.",
        "100.100.", "100.101.", "100.102.", "100.103.", "100.104.", "100.105.",
        "100.106.", "100.107.", "100.108.", "100.109.", "100.110.", "100.111.",
        "100.112.", "100.113.", "100.114.", "100.115.", "100.116.", "100.117.",
        "100.118.", "100.119.", "100.120.", "100.121.", "100.122.", "100.123.",
        "100.124.", "100.125.", "100.126.", "100.127.",
        "169.254.", "224.", "239.", "127.", "0.", "255.",
    ]
    
    for ignore in ignore_networks:
        if ip.startswith(ignore):
            return False
    
    return True

def is_infrastructure_ip(ip):
    """Check if IP is infrastructure (not a client)"""
    if ip.startswith(('224.', '239.', '255.', '0.')):
        return True
    if ip.startswith('192.168.'):
        return True
    return False

# =========================
# MIKROTIK API CONNECTION
# =========================
class MikroTikConnector:
    """Connect to MikroTik using API (port 8728)."""

    def __init__(self):
        self.api_pool = None
        self.api = None
        self.connected = False

    def connect(self):
        try:
            logger.info(f"Connecting to MikroTik API {MIKROTIK_HOST}:{MIKROTIK_PORT}...")
            self.api_pool = routeros_api.RouterOsApiPool(
                host=MIKROTIK_HOST,
                username=MIKROTIK_USER.strip(),
                password=MIKROTIK_PASSWORD.strip(),
                port=MIKROTIK_PORT,
                use_ssl=False,
                plaintext_login=True
            )
            self.api = self.api_pool.get_api()
            self.connected = True
            logger.info("Connected successfully via API")
            return True
        except Exception as e:
            logger.error(f"Failed to connect via API: {e}")
            self.connected = False
            return False

    def get_pppoe_clients(self):
        """Get PPPoE active clients from PPP section"""
        try:
            if not self.connected:
                if not self.connect():
                    return []
            
            ppp_resource = self.api.get_resource('/ppp/active')
            active = ppp_resource.get()
            
            clients = []
            for client in active:
                address = client.get('address', '')
                if address and is_client_ip(address):
                    clients.append(address)
            
            return clients
        except Exception as e:
            logger.debug(f"Could not get PPPoE clients: {e}")
            return []

    def disconnect(self):
        try:
            if self.api_pool:
                self.api_pool.disconnect()
            self.connected = False
        except Exception:
            pass

# =========================
# NETFLOW COLLECTOR
# =========================
class NetFlowCollector:
    def __init__(self):
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.sock.bind(('0.0.0.0', NETFLOW_PORT))
        self.sock.settimeout(1.0)
        
        # Store flows by source IP AND destination IP (for bidirectional tracking)
        self.flows_by_src_ip = defaultdict(list)
        self.flows_by_dst_ip = defaultdict(list)
        
        # Round-robin queue for clients
        self.client_queue = deque()
        self.analyzed_clients = set()
        
        # Client list from API
        self.pppoe_clients = set()
        
        self.running = True
        
        logger.info(f"NetFlow Collector started on port {NETFLOW_PORT}")
        logger.info(f"Waiting for data from MikroTik ({MIKROTIK_HOST})...")
        
    def parse_netflow_v5(self, data):
        """Parse NetFlow v5 packet"""
        if len(data) < 24:
            return []
            
        version, count = struct.unpack('>HH', data[0:4])
        
        flows = []
        offset = 24
        
        for i in range(count):
            if offset + 48 > len(data):
                break
                
            src_addr = data[offset:offset+4]
            dst_addr = data[offset+4:offset+8]
            packets, bytes_count = struct.unpack('>II', data[offset+16:offset+24])
            first_ts, last_ts = struct.unpack('>II', data[offset+24:offset+32])
            src_port, dst_port = struct.unpack('>HH', data[offset+32:offset+36])
            tcp_flags = data[offset+37] & 0xFF
            protocol = data[offset+38] & 0xFF
            
            src_ip = socket.inet_ntoa(src_addr)
            dst_ip = socket.inet_ntoa(dst_addr)
            
            # Skip infrastructure IPs
            if is_infrastructure_ip(src_ip) or is_infrastructure_ip(dst_ip):
                offset += 48
                continue
            
            flow = {
                'src_ip': src_ip,
                'dst_ip': dst_ip,
                'src_port': src_port,
                'dst_port': dst_port,
                'protocol': protocol,
                'tcp_flags': tcp_flags,
                'packets': packets,
                'bytes': bytes_count,
                'duration_ms': last_ts - first_ts,
                'timestamp': datetime.now().isoformat()
            }
            
            flows.append(flow)
            offset += 48
            
        return flows

    def update_client_list(self, api_clients):
        """Update client list from MikroTik API"""
        self.pppoe_clients = set(api_clients)
        
        # Add new clients to queue
        for client in self.pppoe_clients:
            if client not in self.analyzed_clients:
                self.client_queue.append(client)
                self.analyzed_clients.add(client)
                logger.info(f"New PPPoE client added to queue: {client}")
        
        logger.info(f"Total PPPoE clients: {len(self.pppoe_clients)}")
        logger.info(f"Clients in analysis queue: {len(self.client_queue)}")

    def get_next_client(self):
        """Get the next client in round-robin queue"""
        if not self.client_queue:
            return None
        self.client_queue.rotate(-1)
        return self.client_queue[0]

    def calculate_api_payload(self, client_ip, all_flows):
        """Convert NetFlow data to match Render API expected format"""
        # Get flows where client is source (outgoing)
        outgoing_flows = [f for f in all_flows if f['src_ip'] == client_ip]
        
        # Get flows where client is destination (incoming)
        incoming_flows = [f for f in all_flows if f['dst_ip'] == client_ip]
        
        # Also look for flows that might be part of the same conversation
        # For UDP, we need to match by (src_ip, dst_ip, src_port, dst_port) pairs
        # But for now, just use the direct classification
        
        logger.info(f"  Flow Classification for {client_ip}:")
        logger.info(f"    Outgoing flows (client as source): {len(outgoing_flows)}")
        logger.info(f"    Incoming flows (client as destination): {len(incoming_flows)}")
        
        # Use recent flows for analysis (last 200 of each type)
        recent_outgoing = outgoing_flows[-200:] if outgoing_flows else []
        recent_incoming = incoming_flows[-200:] if incoming_flows else []
        
        # Combine all flows for total calculations
        all_recent = recent_outgoing + recent_incoming
        
        if not all_recent:
            logger.info(f"  No recent flows for {client_ip}")
            return None
        
        # Calculate aggregates for all flows
        total_packets = sum(f['packets'] for f in all_recent)
        total_bytes = sum(f['bytes'] for f in all_recent)
        total_duration = sum(f['duration_ms'] for f in all_recent)
        
        # Outgoing traffic (FWD)
        fwd_packets = sum(f['packets'] for f in recent_outgoing)
        fwd_bytes = sum(f['bytes'] for f in recent_outgoing)
        
        # Incoming traffic (BWD)
        bwd_packets = sum(f['packets'] for f in recent_incoming)
        bwd_bytes = sum(f['bytes'] for f in recent_incoming)
        
        # Packet lengths for outgoing flows
        fwd_packet_lengths = []
        for f in recent_outgoing:
            if f['packets'] > 0:
                fwd_packet_lengths.extend([f['bytes']/f['packets']] * f['packets'])
        
        # Packet lengths for incoming flows
        bwd_packet_lengths = []
        for f in recent_incoming:
            if f['packets'] > 0:
                bwd_packet_lengths.extend([f['bytes']/f['packets']] * f['packets'])
        
        fwd_len_mean = sum(fwd_packet_lengths)/len(fwd_packet_lengths) if fwd_packet_lengths else 0
        bwd_len_mean = sum(bwd_packet_lengths)/len(bwd_packet_lengths) if bwd_packet_lengths else 0
        
        # Calculate rates
        duration_seconds = total_duration / 1000 if total_duration > 0 else 1
        flow_bytes_s = total_bytes / duration_seconds
        flow_pkts_s = total_packets / duration_seconds
        
        # IAT means (Inter-Arrival Time)
        fwd_iat_mean = total_duration / max(fwd_packets, 1)
        bwd_iat_mean = total_duration / max(bwd_packets, 1)
        
        # Count TCP flags
        syn_count = sum(1 for f in all_recent if f['tcp_flags'] & 0x02)
        ack_count = sum(1 for f in all_recent if f['tcp_flags'] & 0x10)
        fin_count = sum(1 for f in all_recent if f['tcp_flags'] & 0x01)
        rst_count = sum(1 for f in all_recent if f['tcp_flags'] & 0x04)
        
        # Get the most recent flow for context (prefer outgoing, then incoming)
        if recent_outgoing:
            latest_flow = recent_outgoing[-1]
        elif recent_incoming:
            latest_flow = recent_incoming[-1]
        else:
            latest_flow = all_recent[-1]
        
        # Create payload matching your Render API expected format
        payload = {
            # Source/Destination info
            "src_ip": client_ip,
            "dst_ip": latest_flow.get('dst_ip'),
            "src_port": latest_flow.get('src_port'),
            "dst_port": latest_flow.get('dst_port'),
            "protocol": latest_flow.get('protocol'),
            
            # Flow metrics
            "flow_duration": float(total_duration * 1000),  # microseconds
            "flow_pkts_s": float(flow_pkts_s),
            "flow_bytes_s": float(flow_bytes_s),
            
            # Packet counts - NOW WITH BOTH DIRECTIONS!
            "tot_fwd_pkts": int(fwd_packets),
            "tot_bwd_pkts": int(bwd_packets),
            "tot_fwd_bytes": int(fwd_bytes),
            "tot_bwd_bytes": int(bwd_bytes),
            
            # Packet length means
            "fwd_pkt_len_mean": float(fwd_len_mean),
            "bwd_pkt_len_mean": float(bwd_len_mean),
            
            # IAT means
            "fwd_iat_mean": float(fwd_iat_mean),
            "bwd_iat_mean": float(bwd_iat_mean),
            
            # Additional metadata
            "total_flows_out": len(recent_outgoing),
            "total_flows_in": len(recent_incoming),
            "syn_count": syn_count,
            "ack_count": ack_count,
            "fin_count": fin_count,
            "rst_count": rst_count,
            
            # Timestamp
            "timestamp": datetime.now().isoformat()
        }
        
        return payload

    def store_flow(self, flow):
        """Store flow in both source and destination indices"""
        self.flows_by_src_ip[flow['src_ip']].append(flow)
        self.flows_by_dst_ip[flow['dst_ip']].append(flow)

    def get_client_flows(self, client_ip):
        """Get all flows where client is either source or destination"""
        src_flows = self.flows_by_src_ip.get(client_ip, [])
        dst_flows = self.flows_by_dst_ip.get(client_ip, [])
        return src_flows + dst_flows

    def send_to_api(self, payload):
        """Send to Render API"""
        try:
            logger.info(f"Sending REAL data for {payload.get('src_ip')} to Render API...")
            
            response = requests.post(API_URL, json=payload, timeout=30)
            
            if response.ok:
                data = response.json()
                risk = data.get('risk_level', 'unknown')
                prob = data.get('prob_attack', 0)
                
                logger.info(f"✓ API Response: {payload['src_ip']} - {risk} (prob: {float(prob):.4f})")
                
                # Log ALL the REAL data being sent to Laravel database
                logger.info("  " + "=" * 80)
                logger.info("  DATA SENT TO LARAVEL DATABASE:")
                logger.info(f"    src_ip: {payload['src_ip']}")
                logger.info(f"    dst_ip: {payload.get('dst_ip')}")
                logger.info(f"    src_port: {payload.get('src_port')}")
                logger.info(f"    dst_port: {payload.get('dst_port')}")
                logger.info(f"    protocol: {payload.get('protocol')}")
                logger.info(f"    flow_duration: {payload['flow_duration']/1e6:.2f}s")
                logger.info(f"    flow_pkts_s: {payload['flow_pkts_s']:.2f}")
                logger.info(f"    flow_bytes_s: {payload['flow_bytes_s']:.2f}")
                logger.info(f"    tot_fwd_pkts (outgoing): {payload['tot_fwd_pkts']}")
                logger.info(f"    tot_bwd_pkts (incoming): {payload['tot_bwd_pkts']}")
                logger.info(f"    tot_fwd_bytes (out): {payload['tot_fwd_bytes']}")
                logger.info(f"    tot_bwd_bytes (in): {payload['tot_bwd_bytes']}")
                logger.info(f"    fwd_pkt_len_mean: {payload['fwd_pkt_len_mean']:.1f}")
                logger.info(f"    bwd_pkt_len_mean: {payload['bwd_pkt_len_mean']:.1f}")
                logger.info(f"    fwd_iat_mean: {payload['fwd_iat_mean']:.2f}ms")
                logger.info(f"    bwd_iat_mean: {payload['bwd_iat_mean']:.2f}ms")
                logger.info(f"    risk_level: {risk}")
                logger.info(f"    prob_attack: {float(prob):.4f}")
                logger.info(f"    attack_type: {data.get('attack_type')}")
                logger.info("  " + "=" * 80)
                
                return True
            else:
                logger.error(f"API error: {response.status_code}")
                logger.error(f"Response: {response.text[:200]}")
                return False
        except Exception as e:
            logger.error(f"API connection error: {e}")
            return False

    def analyze_next_client(self):
        """Analyze the next client in round-robin queue"""
        next_client = self.get_next_client()
        
        if not next_client:
            logger.info("No clients in queue to analyze")
            return
        
        # Get all flows where client is either source or destination
        all_flows = self.get_client_flows(next_client)
        
        if len(all_flows) < MIN_FLOWS_FOR_ANALYSIS:
            logger.info(f"Client {next_client}: Only {len(all_flows)} total flows (need {MIN_FLOWS_FOR_ANALYSIS}) - skipping")
            return
        
        logger.info("=" * 80)
        logger.info(f"ROUND-ROBIN ANALYSIS - Client: {next_client}")
        logger.info(f"Total flows (bidirectional): {len(all_flows)}")
        logger.info(f"Clients in queue: {len(self.client_queue)}")
        logger.info("-" * 80)
        
        # Calculate API payload using all flows
        payload = self.calculate_api_payload(next_client, all_flows)
        
        if payload:
            # Log REAL metrics before sending
            logger.info("REAL NETFLOW METRICS:")
            logger.info(f"  Duration: {payload['flow_duration']/1e6:.2f}s")
            logger.info(f"  Total Packets: {payload['tot_fwd_pkts'] + payload['tot_bwd_pkts']}")
            logger.info(f"  Total Bytes: {payload['tot_fwd_bytes'] + payload['tot_bwd_bytes']}")
            logger.info(f"  Flow Rate: {payload['flow_pkts_s']:.2f} packets/s")
            logger.info(f"  Outgoing Packets (FWD): {payload['tot_fwd_pkts']}")
            logger.info(f"  Incoming Packets (BWD): {payload['tot_bwd_pkts']}")
            logger.info(f"  Outgoing Bytes (FWD): {payload['tot_fwd_bytes']}")
            logger.info(f"  Incoming Bytes (BWD): {payload['tot_bwd_bytes']}")
            logger.info(f"  Outgoing/Incoming Ratio: {payload['tot_fwd_pkts']/max(payload['tot_bwd_pkts'],1):.2f}")
            
            # Send to API
            self.send_to_api(payload)
        
        logger.info("=" * 80)

    def run(self):
        """Main collector loop"""
        last_analysis = time.time()
        last_api_check = time.time()
        
        # Connect to MikroTik API
        mt = MikroTikConnector()
        
        while self.running:
            try:
                data, addr = self.sock.recvfrom(8192)
                
                # Check if from MikroTik
                if addr[0] != MIKROTIK_HOST:
                    continue
                
                if len(data) >= 2:
                    version = struct.unpack('>H', data[0:2])[0]
                    
                    if version == 5:
                        flows = self.parse_netflow_v5(data)
                        
                        for flow in flows:
                            self.store_flow(flow)
                        
                        if flows and len(flows) > 0:
                            logger.info(f"Received {len(flows)} NetFlow v5 flows")
                
                # Get PPPoE clients from API every 5 minutes
                if time.time() - last_api_check > 300:
                    clients = mt.get_pppoe_clients()
                    if clients:
                        self.update_client_list(clients)
                    last_api_check = time.time()
                
                # Analyze one client per interval
                if time.time() - last_analysis > ANALYSIS_INTERVAL:
                    self.analyze_next_client()
                    last_analysis = time.time()
                    
                    # Clean up old flows (keep last hour)
                    cutoff = time.time() - 3600
                    for ip in list(self.flows_by_src_ip.keys()):
                        self.flows_by_src_ip[ip] = [f for f in self.flows_by_src_ip[ip] 
                                                   if datetime.fromisoformat(f['timestamp']).timestamp() > cutoff]
                        if not self.flows_by_src_ip[ip]:
                            del self.flows_by_src_ip[ip]
                    
                    for ip in list(self.flows_by_dst_ip.keys()):
                        self.flows_by_dst_ip[ip] = [f for f in self.flows_by_dst_ip[ip] 
                                                   if datetime.fromisoformat(f['timestamp']).timestamp() > cutoff]
                        if not self.flows_by_dst_ip[ip]:
                            del self.flows_by_dst_ip[ip]
                    
            except socket.timeout:
                continue
            except KeyboardInterrupt:
                logger.info("Stopping collector...")
                self.running = False
                break
            except Exception as e:
                logger.error(f"Error in main loop: {e}")

# =========================
# MAIN
# =========================
class MergedIDS:
    def __init__(self):
        self.netflow = NetFlowCollector()
        
    def run(self):
        logger.info("=" * 80)
        logger.info("MERGED MIKROTIK IDS - API + NETFLOW")
        logger.info("=" * 80)
        logger.info(f"MikroTik API: {MIKROTIK_HOST}:{MIKROTIK_PORT}")
        logger.info(f"NetFlow Port: {NETFLOW_PORT}")
        logger.info(f"Render API: {API_URL}")
        logger.info(f"Analysis Interval: {ANALYSIS_INTERVAL}s")
        logger.info("Mode: Bidirectional flow tracking with REAL NetFlow data")
        logger.info("=" * 80)
        
        # Get initial client list
        mt = MikroTikConnector()
        clients = mt.get_pppoe_clients()
        if clients:
            self.netflow.update_client_list(clients)
        
        # Start NetFlow collector
        self.netflow.run()

if __name__ == "__main__":
    ids = MergedIDS()
    try:
        ids.run()
    except KeyboardInterrupt:
        logger.info("\nStopped by user")
        logger.info("=" * 80)
        logger.info("FINAL STATISTICS")
        logger.info("=" * 80)
