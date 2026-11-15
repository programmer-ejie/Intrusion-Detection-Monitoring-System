<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>IDMS Threat Evidence Report</title>
<style>
@page { margin: 28px 30px 35px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #26364a; }
h1 { font-size: 22px; color: #5356c9; margin-bottom: 5px; }
h2 { font-size: 15px; margin-top: 20px; } h3 { font-size: 12px; margin-top: 16px; }
.report-muted { color: #627084; font-size: 9px; }
.data { width:100%; border-collapse:collapse; table-layout:fixed; }
.data th { background:#eeedff; text-align:left; }
.data td, .data th { padding:6px 4px; border-bottom:1px solid #dce0e5; overflow-wrap:break-word; }
thead { display: table-header-group; } tr { page-break-inside:avoid; }
.page-break { page-break-before:always; }
.metrics { padding:12px; background:#f4f5fa; margin:15px 0; font-size:11px; }
</style></head><body>
<h1>IDMS | Threat Evidence Report</h1>
<p>Generated: {{ $generatedAt->format('Y-m-d H:i:s T') }} | Window: {{ ['24h'=>'Last 24 hours','7d'=>'Last 7 days','30d'=>'Last 30 days','all'=>'All history'][$window] }} | Client: {{ $clientFilter ?: 'All clients' }}</p>
<p class="report-muted">Event timestamps use {{ config('app.timezone') }}. Includes blocked and resolved history. Predictions indicate suspected activity; reviewed outcomes reflect administrator assessments and supporting notes.</p>
<div class="metrics">Events: {{ number_format($counts->total) }} &nbsp; | &nbsp; Predicted attacks: {{ number_format($counts->attacks) }} &nbsp; | &nbsp; High risk (&gt;80%): {{ number_format($counts->high_risk) }} &nbsp; | &nbsp; Flagged clients: {{ $clients->count() }}</div>
@include('admin.reports.charts')
<h2 class="page-break">Client summary</h2>
<table class="data"><thead><tr><th>Client</th><th>Events</th><th>Predicted attacks</th><th>False positives</th><th>False negatives</th><th>First seen</th><th>Last seen</th></tr></thead><tbody>
@forelse($clients as $client)
<tr><td>{{ $client->client }}</td><td>{{ $client->events }}</td><td>{{ $client->attempts }}</td><td>{{ $client->fp }}</td><td>{{ $client->fn }}</td><td>{{ $client->first_seen }}</td><td>{{ $client->last_seen }}</td></tr>
@empty<tr><td colspan="7">No flagged clients in this period.</td></tr>@endforelse
</tbody></table>
<h2 class="page-break">Client event evidence</h2>
<p class="report-muted">Latest {{ $rows->count() }} of {{ number_format($evidenceCount) }} matching events for flagged clients, including benign predictions. PDF detail is limited to 100 events; use Export CSV for all filtered events. Summary counts and charts use the complete filtered dataset.</p>
<table class="data"><thead><tr><th style="width:7%">Event ID</th><th style="width:14%">Timestamp / Client</th><th style="width:17%">Source → Destination</th><th style="width:14%">Prediction / Type / Confidence</th><th style="width:13%">Reviewed outcome</th><th style="width:25%">Review evidence</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>#{{ $row->id }}</td><td>{{ $row->created_at }}<br>{{ $row->pppoe_name ?: $row->src_ip }}</td><td>{{ $row->src_ip }}:{{ $row->src_port }}<br>→ {{ $row->dst_ip }}:{{ $row->dst_port }}<br>Protocol: {{ $row->protocol }}</td><td>{{ $row->risk_level }}<br>{{ $row->attack_type ?: '—' }}<br>{{ number_format($row->prob_attack * 100, 2) }}%</td><td>{{ \App\Services\ThreatReportService::outcome($row) }}<br>Status: {{ $row->status ?: '—' }}</td><td>{{ $row->review_notes ?: 'No review evidence recorded.' }}<br>@if($row->reviewed_at)Reviewer #{{ $row->reviewed_by }} · {{ $row->reviewed_at }}@endif</td></tr>
@empty<tr><td colspan="6">No client event evidence in this period.</td></tr>@endforelse
</tbody></table>
</body></html>
