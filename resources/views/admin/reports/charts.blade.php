<h3>Client attack attempts</h3>
<p class="report-muted">Clients with predicted or reviewed attacks in this period. Identity uses PPPoE name, falling back to source IP. Bars show model detections, not confirmed attacks. Top 20 clients; summary below includes all {{ $clients->count() }} clients.</p>
@php($maximum = max(1, (int) $clients->max('attempts')))
@forelse($clients->take(20) as $client)
  <table class="report-bar-row" style="width:100%; margin-bottom:7px"><tr>
    <td style="width:28%;word-break:break-all">{{ $client->client }}</td>
    <td style="width:62%"><div style="background:#ecebff;height:14px"><div style="background:#696cff;height:14px;width:{{ max(0, (int) $client->attempts / $maximum * 100) }}%"></div></div></td>
    <td style="width:10%;text-align:right">{{ number_format($client->attempts) }}</td>
  </tr></table>
@empty
  <p>No clients with predicted or reviewed attacks in this period.</p>
@endforelse

<h3>Reviewed detection outcomes</h3>
<p class="report-muted">Across all events matching the selected filters. False positive: predicted attack, reviewed benign. False negative: predicted benign, reviewed attack. Outcomes require an administrator's evidence-based review; unreviewed events are not counted as correct or incorrect.</p>
@php($outcomes = ['True positive' => ['tp', '#278565'], 'False positive' => ['fp', '#e69b24'], 'False negative' => ['fn', '#d94b56'], 'True negative' => ['tn', '#4299b5'], 'Unreviewed' => ['unreviewed', '#8892a2']])
@php($outcomeMax = max(1, ...array_map(fn ($entry) => (int) $counts->{$entry[0]}, array_values($outcomes))))
@foreach($outcomes as $label => [$key, $color])
  <table class="report-bar-row" style="width:100%;margin-bottom:7px"><tr>
    <td style="width:28%">{{ $label }}</td>
    <td style="width:62%"><div style="background:#f1f2f4;height:14px"><div style="background:{{ $color }};height:14px;width:{{ (int) $counts->$key / $outcomeMax * 100 }}%"></div></div></td>
    <td style="width:10%;text-align:right">{{ number_format($counts->$key) }}</td>
  </tr></table>
@endforeach
