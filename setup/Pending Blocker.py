#!/usr/bin/env python
"""
Polls Hostinger MySQL for blocked threats and keeps destination IPs synced on MikroTik.
Run this on a machine that can reach the MikroTik router.
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
import hashlib
import time
import logging

import pymysql
from dotenv import load_dotenv

try:
    import routeros_api
except ImportError:
    raise SystemExit(
        "Missing dependency: routeros-api\n"
        "Install it with: pip install routeros-api"
    )

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(BASE_DIR, ".env"))

LOG_DIR = os.path.join(BASE_DIR, "logs")
os.makedirs(LOG_DIR, exist_ok=True)

logging.basicConfig(
    level=logging.DEBUG,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(os.path.join(LOG_DIR, "blocked_threat_sync.log"), encoding="utf-8"),
        logging.StreamHandler(),
    ],
)
logger = logging.getLogger("blocked_threat_sync")

DB_HOST = os.getenv("DB_HOST", "127.0.0.1").strip()
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = os.getenv("DB_DATABASE", "").strip()
DB_USER = os.getenv("DB_USERNAME", "").strip()
DB_PASSWORD = os.getenv("DB_PASSWORD", "").strip()
DB_TABLE = os.getenv("DB_TABLE", "intrusion_logs").strip()

MIKROTIK_HOST = os.getenv("MIKROTIK_HOST", "192.168.96.1").strip()
MIKROTIK_USER = os.getenv("MIKROTIK_USER", "admin").strip()
MIKROTIK_PASSWORD = os.getenv("MIKROTIK_PASSWORD", "").strip()
MIKROTIK_PORT = int(os.getenv("MIKROTIK_PORT", "8728"))
BLOCK_SCAN_INTERVAL = int(os.getenv("BLOCK_SCAN_INTERVAL", "20"))
BLOCK_RULE_PREFIX = os.getenv("BLOCK_RULE_PREFIX", "IDS block").strip()
PPPOE_TARGETS = {
    "10.0.70.2": {
        "ppp_name": "jimson",
        "label": "pppoe-jimson",
    },
    "10.0.70.5": {
        "ppp_name": "jimson2",
        "label": "pppoe-jimson2",
    },
}


class RouterOsDirectClient:
    def __init__(self, host: str, port: int, username: str, password: str, timeout: int = 10):
        self.host = host
        self.port = port
        self.username = username
        self.password = password
        self.timeout = timeout
        self.sock = None
        logger.debug(f"RouterOsDirectClient initialized for {host}:{port}")

    def __enter__(self):
        logger.debug(f"Connecting to RouterOS at {self.host}:{self.port}")
        self.sock = socket.create_connection((self.host, self.port), timeout=self.timeout)
        self.sock.settimeout(self.timeout)
        self._login()
        logger.debug("RouterOS connection established and logged in")
        return self

    def __exit__(self, exc_type, exc, tb):
        try:
            if self.sock:
                self.sock.close()
                logger.debug("RouterOS connection closed")
        finally:
            self.sock = None

    def _write_len(self, length: int):
        if length < 0x80:
            self.sock.sendall(bytes([length]))
        elif length < 0x4000:
            length |= 0x8000
            self.sock.sendall(bytes([(length >> 8) & 0xFF, length & 0xFF]))
        elif length < 0x200000:
            length |= 0xC00000
            self.sock.sendall(bytes([
                (length >> 16) & 0xFF,
                (length >> 8) & 0xFF,
                length & 0xFF,
            ]))
        elif length < 0x10000000:
            length |= 0xE0000000
            self.sock.sendall(bytes([
                (length >> 24) & 0xFF,
                (length >> 16) & 0xFF,
                (length >> 8) & 0xFF,
                length & 0xFF,
            ]))
        else:
            self.sock.sendall(bytes([
                0xF0,
                (length >> 24) & 0xFF,
                (length >> 16) & 0xFF,
                (length >> 8) & 0xFF,
                length & 0xFF,
            ]))

    def _write_word(self, word: str):
        data = word.encode("utf-8")
        self._write_len(len(data))
        self.sock.sendall(data)

    def _write_sentence(self, words):
        for word in words:
            self._write_word(word)
        self._write_word("")

    def _read_len(self) -> int:
        first = self.sock.recv(1)
        if not first:
            raise ConnectionError("RouterOS API connection closed")
        c = first[0]
        if (c & 0x80) == 0x00:
            return c
        if (c & 0xC0) == 0x80:
            b2 = self.sock.recv(1)[0]
            return ((c & ~0xC0) << 8) + b2
        if (c & 0xE0) == 0xC0:
            b2 = self.sock.recv(1)[0]
            b3 = self.sock.recv(1)[0]
            return ((c & ~0xE0) << 16) + (b2 << 8) + b3
        if (c & 0xF0) == 0xE0:
            b2 = self.sock.recv(1)[0]
            b3 = self.sock.recv(1)[0]
            b4 = self.sock.recv(1)[0]
            return ((c & ~0xF0) << 24) + (b2 << 16) + (b3 << 8) + b4
        b2 = self.sock.recv(1)[0]
        b3 = self.sock.recv(1)[0]
        b4 = self.sock.recv(1)[0]
        b5 = self.sock.recv(1)[0]
        return (b2 << 24) + (b3 << 16) + (b4 << 8) + b5

    def _read_word(self) -> str:
        length = self._read_len()
        if length == 0:
            return ""
        data = b""
        while len(data) < length:
            chunk = self.sock.recv(length - len(data))
            if not chunk:
                raise ConnectionError("RouterOS API connection closed while reading word")
            data += chunk
        return data.decode("utf-8", errors="replace")

    def _read_sentence(self):
        words = []
        while True:
            word = self._read_word()
            if word == "":
                return words
            words.append(word)

    def _login(self):
        try:
            logger.debug("Attempting login with username/password")
            self._write_sentence(["/login", f"=name={self.username}", f"=password={self.password}"])
            reply = self._read_sentence()
            if any(word == "!done" for word in reply):
                logger.debug("Login successful")
                return
        except Exception as e:
            logger.debug(f"Standard login failed, trying challenge-response: {e}")

        logger.debug("Attempting challenge-response login")
        self._write_sentence(["/login"])
        reply = self._read_sentence()
        ret = None
        for word in reply:
            if word.startswith("=ret="):
                ret = word.split("=", 2)[2]
                break
        if not ret:
            raise RuntimeError(f"RouterOS login failed: {reply}")

        challenge = bytes.fromhex(ret)
        md5 = hashlib.md5(b"\x00" + self.password.encode("utf-8") + challenge).hexdigest()
        response = "00" + md5
        self._write_sentence(["/login", f"=name={self.username}", f"=response={response}"])
        done = self._read_sentence()
        if not any(word == "!done" for word in done):
            raise RuntimeError(f"RouterOS login failed: {done}")
        logger.debug("Challenge-response login successful")

    def run(self, command: str, args: dict | None = None):
        words = [command]
        for key, value in (args or {}).items():
            words.append(f"={key}={value}")
        logger.debug(f"Sending RouterOS command: {command} with args: {args}")
        self._write_sentence(words)
        reply = self._read_sentence()
        if any(word == "!trap" for word in reply):
            error_msg = f"RouterOS command failed: {reply}"
            logger.error(error_msg)
            raise RuntimeError(error_msg)
        logger.debug(f"RouterOS command successful, reply: {reply}")
        return reply


def db_connect():
    logger.debug(f"Connecting to MySQL database: {DB_HOST}:{DB_PORT}/{DB_NAME}")
    return pymysql.connect(
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME,
        charset="utf8mb4",
        autocommit=True,
    )


def get_status_groups():
    if not DB_NAME or not DB_USER:
        raise RuntimeError("Missing DB credentials in .env")

    query = f"""
        SELECT
            src_ip,
            MAX(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) AS has_blocked,
            MAX(CASE WHEN status IN ('resolved', 'restored') THEN 1 ELSE 0 END) AS has_resolved
        FROM {DB_TABLE}
        WHERE status IN ('blocked', 'resolved', 'restored')
          AND src_ip IS NOT NULL
          AND src_ip <> ''
        GROUP BY src_ip
    """
    logger.debug(f"Executing database query: {query}")
    with db_connect() as conn:
        with conn.cursor() as cursor:
            cursor.execute(query)
            rows = cursor.fetchall()
    logger.debug(f"Database query returned {len(rows)} rows")
    return rows


class MikroTikBlocker:
    def __init__(self):
        self.pool = None
        self.api = None
        logger.info("MikroTikBlocker initialized")

    def _rule_comment(self, dst_ip: str) -> str:
        comment = f"{BLOCK_RULE_PREFIX} {dst_ip}"
        logger.debug(f"Generated rule comment: {comment}")
        return comment

    def _format_rule(self, row: dict) -> str:
        # Get ID from either 'id' or '.id'
        rule_id = row.get(".id") or row.get("id") or "?"
        comment = row.get("comment") or ""
        src_address = row.get("src-address") or row.get("src_address") or ""
        action = row.get("action") or ""
        chain = row.get("chain") or ""
        disabled = row.get("disabled") or "no"
        return f"id={rule_id} chain={chain} action={action} src={src_address} disabled={disabled} comment={comment}"

    def _get_rule_id(self, row: dict) -> str:
        """Get rule ID from either 'id' or '.id' key, preserving the asterisk"""
        # Try both possible keys
        rule_id = row.get(".id") or row.get("id")
        if rule_id:
            # Return the ID as-is (with asterisk if present)
            return str(rule_id)
        return None

    def connect(self):
        logger.info(f"Connecting to MikroTik at {MIKROTIK_HOST}:{MIKROTIK_PORT}")
        self.pool = routeros_api.RouterOsApiPool(
            host=MIKROTIK_HOST,
            username=MIKROTIK_USER,
            password=MIKROTIK_PASSWORD,
            port=MIKROTIK_PORT,
            use_ssl=False,
            plaintext_login=True,
        )
        self.api = self.pool.get_api()
        logger.info("MikroTik connection established")

    def ensure_connected(self):
        if self.api is None:
            logger.debug("API not connected, establishing connection")
            self.connect()
        else:
            logger.debug("API already connected")

    def is_already_blocked(self, dst_ip: str) -> bool:
        self.ensure_connected()
        logger.debug(f"Checking if {dst_ip} is already blocked")
        resource = self.api.get_resource("/ip/firewall/filter")
        existing = [
            row for row in resource.get(
                chain="forward",
                action="drop",
                comment=self._rule_comment(dst_ip),
            )
            if str(row.get("disabled") or "").lower() not in {"true", "yes", "1"}
        ]
        logger.info(
            "Block check for %s matched %d enabled firewall rule(s)",
            dst_ip,
            len(existing),
        )
        if existing:
            logger.debug(f"Existing rules for {dst_ip}: {', '.join(self._format_rule(row) for row in existing)}")
        return bool(existing)

    def _get_matching_rules(self, dst_ip: str):
        self.ensure_connected()
        resource = self.api.get_resource("/ip/firewall/filter")
        comment = self._rule_comment(dst_ip)
        logger.debug(f"Getting matching rules for {dst_ip} with comment: {comment}")
        rows = resource.get(
            chain="forward",
            action="drop",
            comment=comment,
        )
        logger.debug(f"Found {len(rows)} matching rules for {dst_ip}")
        return resource, rows

    def block_ip(self, dst_ip: str):
        logger.info(f"=== STARTING BLOCK OPERATION FOR {dst_ip} ===")
        resource, rows = self._get_matching_rules(dst_ip)
        comment = self._rule_comment(dst_ip)
        
        enabled_rows = [
            row for row in rows
            if str(row.get("disabled") or "").lower() not in {"true", "yes", "1"}
        ]
        disabled_rows = [
            row for row in rows
            if str(row.get("disabled") or "").lower() in {"true", "yes", "1"}
        ]
        
        logger.debug(f"Enabled rules: {len(enabled_rows)}, Disabled rules: {len(disabled_rows)}")
        
        if enabled_rows:
            logger.info(f"Firewall drop rule already exists for {dst_ip} (enabled)")
            logger.debug(f"Existing enabled rules: {', '.join(self._format_rule(row) for row in enabled_rows)}")
            logger.info(f"=== BLOCK OPERATION COMPLETED FOR {dst_ip} (already blocked) ===")
            return

        if disabled_rows:
            logger.info(f"Found {len(disabled_rows)} disabled firewall rules for {dst_ip}, re-enabling them")
            for row in disabled_rows:
                rule_id = self._get_rule_id(row)
                if rule_id:
                    logger.info(f"Re-enabling firewall rule for {dst_ip}: {self._format_rule(row)}")
                    self._set_firewall_rule_enabled(resource, rule_id, dst_ip, True)
                else:
                    logger.warning(f"Cannot re-enable rule without ID: {row}")
            logger.info(f"Re-enabled existing disabled firewall drop rule(s) for {dst_ip}")
        else:
            logger.info(f"No existing rules found for {dst_ip}, creating new drop rule")
            resource.add(
                chain="forward",
                src_address=dst_ip,
                action="drop",
                comment=comment,
            )
            logger.info(f"Created new firewall drop rule for {dst_ip} with comment: {comment}")
        
        # Clear existing tracked connections so the firewall change takes effect quickly.
        logger.info(f"Clearing active connections for {dst_ip}")
        self.clear_connections(dst_ip)
        
        # Verify the block was successful
        logger.debug(f"Verifying block for {dst_ip}")
        verification = resource.get(
            chain="forward",
            action="drop",
            comment=comment,
        )
        enabled_verification = [
            row for row in verification
            if str(row.get("disabled") or "").lower() not in {"true", "yes", "1"}
        ]
        if enabled_verification:
            logger.info(f"Block verification successful for {dst_ip}: {len(enabled_verification)} rule(s) active")
        else:
            logger.warning(f"Block verification failed for {dst_ip}: No active rules found!")
        
        logger.info(f"=== BLOCK OPERATION COMPLETED FOR {dst_ip} ===")

    def unblock_ip(self, dst_ip: str):
        logger.info(f"=== STARTING UNBLOCK OPERATION FOR {dst_ip} ===")
        resource, rows = self._get_matching_rules(dst_ip)
        
        logger.info(
            "Restore check for %s matched %d firewall rule(s)",
            dst_ip,
            len(rows),
        )
        
        if not rows:
            logger.info(f"No firewall drop rules found for {dst_ip}, nothing to remove")
            logger.info(f"=== UNBLOCK OPERATION COMPLETED FOR {dst_ip} (no rules found) ===")
            return

        # Log all rules found
        for idx, row in enumerate(rows, 1):
            logger.info(f"Rule {idx} found for {dst_ip}: {self._format_rule(row)}")

        # Remove all matching rules
        removed_count = 0
        for row in rows:
            rule_id = self._get_rule_id(row)
            if rule_id:
                logger.info(f"ATTEMPTING to remove firewall rule for {dst_ip}: {self._format_rule(row)}")
                success = self._remove_firewall_rule(resource, rule_id, dst_ip)
                if success:
                    removed_count += 1
                    logger.info(f"SUCCESSFULLY removed rule {rule_id} for {dst_ip}")
                else:
                    logger.error(f"FAILED to remove rule {rule_id} for {dst_ip}")
            else:
                logger.warning(f"Rule has no ID, cannot remove: {row}")
                # Try to remove by comment as fallback
                logger.info(f"Attempting to remove rule by comment for {dst_ip}")
                try:
                    comment = self._rule_comment(dst_ip)
                    # Try to remove using routeros-api with comment filter
                    logger.info(f"Removing all rules with comment: {comment}")
                    # Get all rules with this comment and remove them
                    all_rules = resource.get(comment=comment)
                    for r in all_rules:
                        r_id = self._get_rule_id(r)
                        if r_id:
                            logger.info(f"Removing rule by comment: {r_id}")
                            self._remove_firewall_rule(resource, r_id, dst_ip)
                    removed_count += 1
                    logger.info(f"Removed rule by comment for {dst_ip}")
                except Exception as e:
                    logger.error(f"Failed to remove rule by comment for {dst_ip}: {e}")

        # Verify the rules are gone
        logger.debug(f"Verifying removal for {dst_ip}")
        remaining = resource.get(
            chain="forward",
            action="drop",
            comment=self._rule_comment(dst_ip),
        )
        
        if remaining:
            logger.warning(
                "Restore verification FAILED for %s; %d matching rule(s) still remain",
                dst_ip,
                len(remaining),
            )
            for idx, row in enumerate(remaining, 1):
                logger.warning(f"Remaining rule {idx}: {self._format_rule(row)}")
        else:
            logger.info(f"Restore verification PASSED for {dst_ip}; all {removed_count} firewall rule(s) removed")
        
        # Clear existing tracked connections to help immediate restoration.
        logger.info(f"Clearing active connections for {dst_ip}")
        self.clear_connections(dst_ip)

        logger.info(f"=== UNBLOCK OPERATION COMPLETED FOR {dst_ip} (removed {removed_count} rule(s)) ===")

    def resolve_current_ip_for_source(self, src_ip: str) -> str:
        target = PPPOE_TARGETS.get(src_ip)
        if not target:
            raise RuntimeError(f"No PPPoE target mapping configured for source IP {src_ip}")

        self.ensure_connected()
        resource = self.api.get_resource("/ppp/active")
        rows = resource.get(name=target["ppp_name"])
        if not rows:
            all_rows = resource.get()
            rows = [
                row for row in all_rows
                if str(row.get("name") or "").strip().lower() == target["ppp_name"].lower()
                or target["ppp_name"].lower() in str(row.get("name") or "").strip().lower()
            ]

        if not rows:
            raise RuntimeError(
                f"Could not find an active PPP session for source IP {src_ip} ({target['ppp_name']})"
            )

        current_ip = str(rows[0].get("address") or "").strip()
        if not current_ip:
            raise RuntimeError(
                f"PPP session for source IP {src_ip} ({target['ppp_name']}) does not have an assigned address"
            )

        logger.info(
            "Resolved source IP %s to live destination IP %s via PPPoE %s",
            src_ip,
            current_ip,
            target["label"],
        )
        return current_ip

    def _set_firewall_rule_enabled(self, resource, rule_id: str, dst_ip: str, enabled: bool):
        logger.debug(f"Setting firewall rule {rule_id} enabled={enabled} for {dst_ip}")
        value = "no" if enabled else "yes"
        status_text = "enable" if enabled else "disable"
        
        # Keep the asterisk if present
        clean_id = rule_id.lstrip("*")
        
        # Try direct API with different parameter formats
        attempts = [
            {"numbers": rule_id, "disabled": value},
            {"numbers": clean_id, "disabled": value},
            {".id": rule_id, "disabled": value},
            {".id": clean_id, "disabled": value},
        ]
        
        for idx, attempt in enumerate(attempts, 1):
            try:
                logger.debug(f"Attempt {idx} to {status_text} rule for {dst_ip}: {attempt}")
                with RouterOsDirectClient(MIKROTIK_HOST, MIKROTIK_PORT, MIKROTIK_USER, MIKROTIK_PASSWORD) as client:
                    client.run("/ip/firewall/filter/set", attempt)
                logger.info(
                    f"Successfully {status_text}d firewall rule for {dst_ip} using attempt {idx}: {', '.join(f'{k}={v}' for k, v in attempt.items())}"
                )
                return True
            except Exception as exc:
                logger.warning(
                    f"Firewall {status_text} attempt {idx} failed for {dst_ip} with params {', '.join(f'{k}={v}' for k, v in attempt.items())}: {exc}"
                )
        
        # Try using the resource object as fallback
        try:
            logger.debug(f"Attempting to {status_text} rule using resource object for {dst_ip}")
            if enabled:
                resource.enable(id=rule_id)
            else:
                resource.disable(id=rule_id)
            logger.info(f"Successfully {status_text}d firewall rule for {dst_ip} using resource object")
            return True
        except Exception as exc:
            logger.warning(f"Resource object {status_text} failed for {dst_ip}: {exc}")
        
        logger.error(f"All attempts to {status_text} firewall rule for {dst_ip} failed")
        return False

    def _remove_firewall_rule(self, resource, rule_id: str, dst_ip: str) -> bool:
        """Remove a firewall rule completely (delete it)"""
        logger.info(f"REMOVE: Starting removal of firewall rule {rule_id} for {dst_ip}")
        
        # Keep the asterisk if present, but also try without it
        clean_id = rule_id.lstrip("*")
        
        # Try different methods to remove the rule
        methods = [
            ("resource.remove with asterisk", lambda: resource.remove(id=rule_id)),
            ("resource.remove clean", lambda: resource.remove(id=clean_id)),
            ("resource.remove numbers with asterisk", lambda: resource.remove(numbers=rule_id)),
            ("resource.remove numbers clean", lambda: resource.remove(numbers=clean_id)),
            ("direct API .id with asterisk", lambda: self._remove_via_direct_api({".id": rule_id})),
            ("direct API .id clean", lambda: self._remove_via_direct_api({".id": clean_id})),
        ]
        
        for method_name, method_func in methods:
            try:
                logger.info(f"REMOVE: Trying method: {method_name} for {dst_ip}")
                method_func()
                logger.info(f"REMOVE: Successfully removed rule {rule_id} for {dst_ip} using {method_name}")
                return True
            except Exception as exc:
                logger.warning(f"REMOVE: Method {method_name} failed for {dst_ip}: {exc}")
        
        # Try the direct Winbox-style command as a last resort
        try:
            logger.info(f"REMOVE: Trying Winbox-style remove for {dst_ip}")
            with RouterOsDirectClient(MIKROTIK_HOST, MIKROTIK_PORT, MIKROTIK_USER, MIKROTIK_PASSWORD) as client:
                # Try with asterisk
                try:
                    client.run(f"/ip/firewall/filter/remove {rule_id}")
                    logger.info(f"REMOVE: Successfully removed rule {rule_id} using Winbox-style command")
                    return True
                except:
                    # Try without asterisk
                    client.run(f"/ip/firewall/filter/remove {clean_id}")
                    logger.info(f"REMOVE: Successfully removed rule {clean_id} using Winbox-style command")
                    return True
        except Exception as exc:
            logger.warning(f"REMOVE: Winbox-style remove failed for {dst_ip}: {exc}")
        
        logger.error(f"REMOVE: All removal methods failed for rule {rule_id} for {dst_ip}")
        return False
    
    def _remove_via_direct_api(self, params: dict):
        """Remove a firewall rule using direct RouterOS API"""
        logger.debug(f"Direct API removal with params: {params}")
        with RouterOsDirectClient(MIKROTIK_HOST, MIKROTIK_PORT, MIKROTIK_USER, MIKROTIK_PASSWORD) as client:
            client.run("/ip/firewall/filter/remove", params)

    def clear_status(self, src_ip: str):
        logger.info(f"Clearing database status for source IP {src_ip}")
        if not DB_NAME or not DB_USER:
            raise RuntimeError("Missing DB credentials in .env")

        query = f"""
            UPDATE {DB_TABLE}
            SET status = NULL
            WHERE src_ip = %s
              AND status IN ('resolved', 'restored')
        """
        logger.debug(f"Executing status clear query: {query}")
        with db_connect() as conn:
            with conn.cursor() as cursor:
                cursor.execute(query, (src_ip,))
                rows_affected = cursor.rowcount
                logger.info(f"Cleared status for source IP {src_ip}; rows affected: {rows_affected}")

    def clear_connections(self, dst_ip: str):
        logger.info(f"Clearing active connections for {dst_ip}")
        self.ensure_connected()
        resource = self.api.get_resource("/ip/firewall/connection")
        
        # Get all connections where src or dst matches
        src_rows = resource.get(src_address=dst_ip)
        dst_rows = resource.get(dst_address=dst_ip)
        rows = src_rows + dst_rows
        
        logger.info(
            "Connection cleanup for %s found %d tracked entries (src: %d, dst: %d)",
            dst_ip,
            len(rows),
            len(src_rows),
            len(dst_rows)
        )

        seen_ids = set()
        removed = 0
        for row in rows:
            rule_id = self._get_rule_id(row)
            if rule_id and rule_id not in seen_ids:
                seen_ids.add(rule_id)
                logger.debug(f"Removing connection for {dst_ip}: {self._format_rule(row)}")
                try:
                    resource.remove(id=rule_id)
                    removed += 1
                    logger.debug(f"Successfully removed connection {rule_id}")
                except Exception as e:
                    logger.warning(f"Failed to remove connection {rule_id} for {dst_ip}: {e}")

        logger.info(f"Cleared {removed} active connection(s) for {dst_ip}")

    def clear_ppp_active(self, dst_ip: str):
        logger.info(f"Clearing active PPP sessions for {dst_ip}")
        self.ensure_connected()
        resource = self.api.get_resource("/ppp/active")
        rows = resource.get(address=dst_ip)

        logger.info(
            "PPP cleanup for %s found %d active session(s)",
            dst_ip,
            len(rows),
        )

        removed = 0
        for row in rows:
            rule_id = self._get_rule_id(row)
            if rule_id:
                logger.debug(f"Removing PPP session for {dst_ip}: {self._format_rule(row)}")
                try:
                    resource.remove(id=rule_id)
                    removed += 1
                    logger.debug(f"Successfully removed PPP session {rule_id}")
                except Exception as e:
                    logger.warning(f"Failed to remove PPP session {rule_id} for {dst_ip}: {e}")

        logger.info(f"Cleared {removed} active PPP session(s) for {dst_ip}")

    def close(self):
        if self.pool:
            try:
                self.pool.disconnect()
                logger.info("MikroTik connection closed")
            except Exception as e:
                logger.warning(f"Error while closing MikroTik connection: {e}")


def main():
    logger.info("=" * 60)
    logger.info("BLOCKED THREAT SYNC SERVICE STARTED")
    logger.info("=" * 60)
    logger.info(f"Configuration:")
    logger.info(f"  - Database: {DB_HOST}:{DB_PORT}/{DB_NAME}")
    logger.info(f"  - Table: {DB_TABLE}")
    logger.info(f"  - MikroTik: {MIKROTIK_HOST}:{MIKROTIK_PORT}")
    logger.info(f"  - Block Rule Prefix: {BLOCK_RULE_PREFIX}")
    logger.info(f"  - Scan Interval: {BLOCK_SCAN_INTERVAL} seconds")
    logger.info("=" * 60)

    blocker = MikroTikBlocker()
    blocker.ensure_connected()

    iteration = 0
    while True:
        iteration += 1
        logger.info(f"--- SCAN ITERATION {iteration} STARTING ---")
        
        try:
            # Get current status from database
            status_groups = get_status_groups()
            blocked_src_ips = [row[0] for row in status_groups if row[0] and int(row[1] or 0) > 0]
            resolved_src_ips = [row[0] for row in status_groups if row[0] and int(row[1] or 0) == 0 and int(row[2] or 0) > 0]

            logger.info(
                "Database scan results: %d source IP(s) to block, %d source IP(s) to unblock",
                len(blocked_src_ips),
                len(resolved_src_ips)
            )

            if blocked_src_ips:
                logger.info(f"Source IPs to BLOCK: {', '.join(blocked_src_ips)}")
            if resolved_src_ips:
                logger.info(f"Source IPs to UNBLOCK: {', '.join(resolved_src_ips)}")

            # Process block operations
            for src_ip in blocked_src_ips:
                try:
                    logger.info(f"Processing BLOCK for source IP {src_ip}")
                    current_ip = blocker.resolve_current_ip_for_source(src_ip)
                    blocker.block_ip(current_ip)
                except Exception as e:
                    logger.error(f"Failed processing blocked source IP {src_ip}: {e}")
                    logger.error(traceback.format_exc())

            # Process unblock operations
            for src_ip in resolved_src_ips:
                try:
                    logger.info(f"Processing UNBLOCK for source IP {src_ip}")
                    current_ip = blocker.resolve_current_ip_for_source(src_ip)
                    blocker.unblock_ip(current_ip)
                    blocker.clear_status(src_ip)
                    logger.info(f"Successfully processed unblock for source IP {src_ip} via live IP {current_ip}")
                except Exception as e:
                    logger.error(f"Failed processing restored/resolved source IP {src_ip}: {e}")
                    logger.error(traceback.format_exc())

            logger.info(f"--- SCAN ITERATION {iteration} COMPLETED ---")

        except Exception as e:
            logger.error(f"Poll error in iteration {iteration}: {e}")
            logger.error(traceback.format_exc())

        logger.debug(f"Sleeping for {BLOCK_SCAN_INTERVAL} seconds before next scan")
        time.sleep(BLOCK_SCAN_INTERVAL)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        logger.info("=" * 60)
        logger.info("BLOCKED THREAT SYNC SERVICE STOPPED BY USER")
        logger.info("=" * 60)
    except Exception as e:
        logger.error(f"Fatal error: {e}")
        logger.error(traceback.format_exc())
