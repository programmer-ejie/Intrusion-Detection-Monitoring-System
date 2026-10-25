"""Feature contract used by the NetFlow-compatible training pipeline."""

SCHEMA_VERSION = "mikrotik-netflow-v1"

NETFLOW_FEATURES = [
    "destination_port",
    "flow_duration",
    "total_fwd_packets",
    "total_backward_packets",
    "total_length_of_fwd_packets",
    "total_length_of_bwd_packets",
    "fwd_packet_length_mean",
    "bwd_packet_length_mean",
    "flow_bytes_s",
    "flow_packets_s",
    "fwd_packets_s",
    "bwd_packets_s",
    "down_up_ratio",
    "average_packet_size",
]

