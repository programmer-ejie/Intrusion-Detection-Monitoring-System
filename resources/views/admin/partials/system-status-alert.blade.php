@php
  // Expect $healthIndicators available
  $statusColor = match($healthIndicators['status']) {
    'Healthy' => 'success',
    'Caution' => 'warning',
    'Warning' => 'danger',
    'Critical' => 'danger',
    default => 'info'
  };
  $statusIcon = match($healthIndicators['status']) {
    'Healthy' => 'bx-check-circle',
    'Caution' => 'bx-info-circle',
    'Warning' => 'bx-error-circle',
    'Critical' => 'bx-x-circle',
    default => 'bx-question-mark'
  };
@endphp
<div id="system-status-alert" class="alert alert-{{ $statusColor }}" role="status" aria-live="polite">
  <strong><i class="bx {{ $statusIcon }}"></i> System Status: {{ $healthIndicators['status'] }}</strong>
  <div class="small mt-1">
    Attack Rate: {{ $healthIndicators['attack_rate'] }}% | Critical Alerts: {{ $healthIndicators['critical_alerts'] }} | Avg Probability: {{ $healthIndicators['avg_prob_attack'] }}
  </div>
</div>
