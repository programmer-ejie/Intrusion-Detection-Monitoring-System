<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="{{ asset('admin/assets') }}/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Intrusion Detection System | System Status</title>

    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}" style = "border-radius: 50%;"/>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/boxicons.css') }}" />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('admin/assets/css/demo.css') }}" />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/apex-charts/apex-charts.css') }}" />

    <script src="{{ asset('admin/assets/vendor/js/helpers.js') }}"></script>

    <script src="{{ asset('admin/assets/js/config.js') }}"></script>
    <style>
      /* Local Cooper font for brand (fallback to Public Sans) */
      @font-face {
        font-family: 'Cooper';
        src: url('/fonts/Cooper.woff2') format('woff2'),
             url('/fonts/Cooper.woff') format('woff');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
      }
      .cooper-brand {
        font-family: 'Cooper', 'Public Sans', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif !important;
        font-weight: 900 !important;
        font-size: 1.6rem !important;
        letter-spacing: 1px !important;
        display: inline-block;
      }
    </style>
  </head>

  <body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
              <span class="app-brand-logo demo">
                <img src="{{ asset('images/logo.png') }}" alt="logo" width="60" height="50" />
              </span>
              <span class="app-brand-text demo menu-text fw-bolder text-uppercase cooper-brand" style="color: rgb(88, 103, 143)">IDSMS</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">HOME</span>
            </li>

            <li class="menu-item">
              <a href="{{route('admin.dashboard')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics" class="fw-semibold">Dashboard</div>
              </a>
            </li>

            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">FUNCTIONS</span>
            </li>

            <li class="menu-item">
              <a href="{{route('admin.logs')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Analytics" class="fw-semibold">Network Logs</div>
              </a>
            </li>

            <li class="menu-item active">
              <a href="{{route('admin.system-status')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-server"></i>
                <div data-i18n="Analytics" class="fw-semibold">System Status</div>
              </a>
            </li>
             <li class="menu-item">
              <a href="{{route('admin.manage-threats')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-shield-alt"></i>
                <div data-i18n="Analytics" class="fw-semibold">Manage Threats</div>
              </a>
            </li>

            <li class="menu-item">
              <a href="{{route('admin.threat-reports')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                <div data-i18n="Analytics" class="fw-semibold">Threat Reports</div>
              </a>
            </li>

            <li class="menu-item">
              <a href="{{route('admin.live')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-tv"></i>
                <div data-i18n="Analytics" class="fw-semibold">Live Monitor</div>
              </a>
            </li>
          </ul>
        </aside>

        <div class="layout-page">

          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar"
          >
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <div class="dropdown">
                    <button
                      class="btn btn-sm btn-outline-primary dropdown-toggle"
                      type="button"
                      id="navActionDropdown"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false"
                    >
                      Filter by Time
                    </button>
                    <div class="dropdown-menu dropdown-menu-start" aria-labelledby="navActionDropdown">
                      <a class="dropdown-item" href="{{ url()->current() }}?window=all">All Time</a>
                      <a class="dropdown-item" href="{{ url()->current() }}?window=24h">Last 24 Hours</a>
                      <a class="dropdown-item" href="{{ url()->current() }}?window=7d">Last 7 Days</a>
                      <a class="dropdown-item" href="{{ url()->current() }}?window=30d">Last 30 Days</a>
                    </div>
                  </div>
                </div>
              </div>

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="{{ asset('images/profile.jpeg') }}" alt class="w-px-40 h-auto rounded-circle" style = "border: 1px solid rgb(57, 57, 93)"/>
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="{{ asset('images/profile.jpeg') }}" alt class="w-px-40 h-auto rounded-circle" style = "border: 1px solid rgb(57, 57, 93)" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block">{{ session('user')->name ?? session('user')->fullname ?? 'Administrator' }}</span>
                            <small class="text-muted">Network Manager</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    <li>
                      <a class="dropdown-item" href="{{route('logout')}}">
                        <i class="bx bx-power-off me-2"></i>
                        <span class="align-middle">Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>

          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
              <!-- Health Status Alert (non-dismissible, auto-updating) -->
              <div class="row mb-4">
                <div class="col-12">
                  <div id="system-status-alert-container">
                    {{-- include initial rendered partial --}}
                    {!! view('admin.partials.system-status-alert', compact('healthIndicators'))->render() !!}
                  </div>
                </div>
              </div>

              <script>
                (function(){
                  const container = document.getElementById('system-status-alert-container');
                  if(!container) return;

                  function fetchAlert(){
                    const params = new URLSearchParams(window.location.search);
                    const url = '/system-status/alert' + (params.toString() ? ('?' + params.toString()) : '');
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                      .then(r => {
                        if(!r.ok) throw new Error('Network response was not ok');
                        return r.text();
                      })
                      .then(html => {
                        container.innerHTML = html;
                      })
                      .catch(err => {
                        // silent fail; keep existing alert
                        console.error('Alert fetch failed', err);
                      });
                  }

                  // Poll every 15 seconds
                  setInterval(fetchAlert, 15000);

                  // Also fetch once after load to ensure freshness
                  fetchAlert();
                })();
              </script>

              <!-- Overview Cards -->
              <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="content-left">
                          <span class="text-muted">Total Logs</span>
                          <div class="h5 fw-bold my-1">{{ number_format($systemStats['total_logs']) }}</div>
                          <small class="text-success text-nowrap"><i class="bx bx-up-arrow-alt"></i>{{ $systemStats['first_log_date'] }}</small>
                        </div>
                        <div class="text-primary d-flex align-items-center justify-content-center" style="min-width:48px">
                          <i class="bx bx-data bx-md fs-4"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="content-left">
                          <span class="text-muted">Logs 
                            @if(request('window') === '7d')
                              (Last 7 Days)
                            @elseif(request('window') === '30d')
                              (Last 30 Days)
                            @elseif(request('window') === 'all')
                              (All Time)
                            @else
                              Today
                            @endif
                          </span>
                          <div class="h5 fw-bold my-1">{{ number_format($systemStats['logs_today']) }}</div>
                          <small class="text-info text-nowrap"><i class="bx bx-info-circle"></i> Last Updated: Now</small>
                        </div>
                        <div class="text-info d-flex align-items-center justify-content-center" style="min-width:48px">
                          <i class="bx bx-time bx-md fs-4"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="content-left">
                          <span class="text-muted">Attacks Detected</span>
                          <div class="h5 fw-bold my-1">{{ number_format($systemStats['total_attacks']) }}</div>
                          <small class="text-danger text-nowrap"><i class="bx bx-alert"></i>
                            @if(request('window') === '7d')
                              Last 7 Days: {{ $systemStats['attacks_today'] }}
                            @elseif(request('window') === '30d')
                              Last 30 Days: {{ $systemStats['attacks_today'] }}
                            @elseif(request('window') === 'all')
                              Total: {{ $systemStats['attacks_today'] }}
                            @else
                              Today: {{ $systemStats['attacks_today'] }}
                            @endif
                          </small>
                        </div>
                        <div class="text-danger d-flex align-items-center justify-content-center" style="min-width:48px">
                          <i class="bx bx-shield-alt-2 bx-md fs-4"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="content-left">
                          <span class="text-muted">System Uptime</span>
                          <div class="h5 fw-bold my-1">
                            @if(is_numeric($systemStats['system_uptime']))
                              @if(!empty($systemStats['uptime_warning']) && $systemStats['system_uptime'] < 0)
                                <span class="text-danger">{{ number_format($systemStats['system_uptime'], 2) }} days</span>
                              @else
                                {{ number_format($systemStats['system_uptime'], 2) }} days
                              @endif
                            @else
                              {{ $systemStats['system_uptime'] }}
                            @endif
                          </div>
                          @if(!empty($systemStats['uptime_warning']) && $systemStats['system_uptime'] < 0)
                            <small class="text-danger mt-1 d-block text-nowrap">Clock skew detected: earliest log timestamp is in the future.</small>
                          @endif
                          <small class="text-warning text-nowrap"><i class="bx bx-server"></i> Active Monitoring</small>
                        </div>
                        <div class="text-warning d-flex align-items-center justify-content-center" style="min-width:48px">
                          <i class="bx bx-check-circle bx-md fs-4"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Processing Metrics -->
              <div class="row mb-4">
                <div class="col-lg-6 mb-3 mb-lg-0">
                  <div class="card h-100">
                    <div class="card-header">
                      <h5 class="mb-0 fw-normal">Processing Metrics</h5>
                    </div>
                    <div class="card-body">
                      <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted">Logs Last Hour</span>
                          <span class="fs-6 text-muted fw-normal">{{ $processingMetrics['logs_last_hour'] }}</span>
                        </div>
                        <div class="progress mt-2">
                          <div class="progress-bar" role="progressbar" style="width: {{ min($processingMetrics['logs_last_hour'] / 100 * 100, 100) }}%"></div>
                        </div>
                      </div>

                      <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted">Logs Last 24 Hours</span>
                          <span class="fs-6 text-muted fw-normal">{{ $processingMetrics['logs_last_day'] }}</span>
                        </div>
                        <div class="progress mt-2">
                          <div class="progress-bar bg-info" role="progressbar" style="width: {{ min($processingMetrics['logs_last_day'] / 1000 * 100, 100) }}%"></div>
                        </div>
                      </div>

                      <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted">Average Hourly Rate</span>
                          <span class="fs-6 text-muted fw-normal">{{ $processingMetrics['average_hourly_rate'] }} logs/hr</span>
                        </div>
                      </div>

                      <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted">Current Hour Rate</span>
                          <span class="fs-6 text-muted fw-normal">{{ $processingMetrics['current_hour_rate'] }} logs/hr</span>
                        </div>
                      </div>

                      <hr>

                      <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted">Avg Packets/Flow</span>
                          <span class="fs-6 text-muted fw-normal">{{ $processingMetrics['avg_packets_per_flow'] }}</span>
                        </div>
                      </div>

                      <div>
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted">Avg Bytes/Flow</span>
                          <span class="fs-6 text-muted fw-normal">{{ number_format($processingMetrics['avg_bytes_per_flow'], 2) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Statistics Summary -->
                <div class="col-lg-6 mb-3 mb-lg-0">
                  <div class="card h-100">
                    <div class="card-header">
                      <h5 class="mb-0 fw-normal">Statistics Summary</h5>
                    </div>
                    <div class="card-body">
                      <div class="mb-4">
                        <h6 class="mb-2 fw-normal">This Week</h6>
                        <div class="row text-center">
                          <div class="col-6">
                            <div class="mb-2">
                              <span class="d-block fs-4 text-muted fw-normal">{{ number_format($systemStats['logs_this_week']) }}</span>
                              <small class="text-muted">Total Logs</small>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="mb-2">
                              <span class="d-block fs-4 text-danger fw-normal">~{{ round(($systemStats['logs_this_week'] / 7)) }}</span>
                              <small class="text-muted">Daily Average</small>
                            </div>
                          </div>
                        </div>
                      </div>

                      <hr>

                      <div class="mb-4">
                        <h6 class="mb-2 fw-normal">This Month</h6>
                        <div class="row text-center">
                          <div class="col-6">
                            <div class="mb-2">
                              <span class="d-block fs-4 text-muted fw-normal">{{ number_format($systemStats['logs_this_month']) }}</span>
                              <small class="text-muted">Total Logs</small>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="mb-2">
                              <span class="d-block fs-4 text-info fw-normal">~{{ round($systemStats['logs_this_month'] / 30) }}</span>
                              <small class="text-muted">Daily Average</small>
                            </div>
                          </div>
                        </div>
                      </div>

                      <hr>

                      <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">First Log:</span>
                        <span class="fw-normal">{{ $systemStats['first_log_date'] }}</span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="text-muted">Last Log:</span>
                        <span class="fw-normal">{{ $systemStats['last_log_date'] }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Health Status Details -->
              <div class="row mb-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="mb-0">Health & Risk Indicators</h5>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                          <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <span class="text-muted d-block">Attack Rate</span>
                                <strong class="display-6 text-danger">{{ $healthIndicators['attack_rate'] }}%</strong>
                              </div>
                              <div class="text-danger">
                                <i class="bx bx-trending-up bx-lg"></i>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                          <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <span class="text-muted d-block">Critical Alerts</span>
                                <strong class="display-6 text-danger">{{ $healthIndicators['critical_alerts'] }}</strong>
                              </div>
                              <div class="text-danger">
                                <i class="bx bx-alarm-exclamation bx-lg"></i>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                          <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <span class="text-muted d-block">High Risk Alerts</span>
                                <strong class="display-6 text-warning">{{ $healthIndicators['high_risk_alerts'] }}</strong>
                              </div>
                              <div class="text-warning">
                                <i class="bx bx-error-circle bx-lg"></i>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                          <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <span class="text-muted d-block">Avg Prob Attack</span>
                                <strong class="display-6 text-info">{{ $healthIndicators['avg_prob_attack'] }}</strong>
                              </div>
                              <div class="text-info">
                                <i class="bx bx-shield-alt-2 bx-lg"></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Activity Timeline -->
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="mb-0">Activity Timeline (Last 24 Hours)</h5>
                    </div>
                    <div class="card-body" style="height: 300px; overflow-y: auto;">
                      <div class="table-responsive">
                        <table class="table table-sm">
                          <thead>
                            <tr>
                              <th>Time</th>
                              <th>Total Logs</th>
                              <th>Attacks</th>
                              <th>Benign</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($activityTimeline as $entry)
                              @php
                                $benign = $entry['total'] - $entry['attacks'];
                              @endphp
                              <tr>
                                <td><strong>{{ $entry['time'] }}</strong></td>
                                <td>
                                  <span class="badge bg-primary">{{ $entry['total'] }}</span>
                                </td>
                                <td>
                                  @if($entry['attacks'] > 0)
                                    <span class="badge bg-danger">{{ $entry['attacks'] }}</span>
                                  @else
                                    <span class="badge bg-secondary">0</span>
                                  @endif
                                </td>
                                <td>
                                  @if($benign > 0)
                                    <span class="badge bg-success">{{ $benign }}</span>
                                  @else
                                    <span class="badge bg-secondary">0</span>
                                  @endif
                                </td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                
              </div>

              
            </div>
             <footer class="content-footer footer bg-footer-theme border-top">
            <div class="container-xxl py-3">
              <div class="row align-items-center">
                <div class="col-md-4 text-center text-md-start mb-2 mb-md-0">
                  <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start">
                    <img src="{{ asset('images/logo.png') }}" alt="IDSMS" style="width:36px;height:36px;border-radius:6px;object-fit:cover" />
                    <div>
                      <div class="fw-bold">IDSMS</div>
                      <small class="text-muted d-block">Intrusion Detection Monitoring</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 text-center mb-2 mb-md-0">
                  <nav class="nav justify-content-center">
                    <a class="nav-link px-2 text-muted" href="#">License</a>
                    <a class="nav-link px-2 text-muted" href="#">Documentation</a>
                    <a class="nav-link px-2 text-muted" href="#">Support</a>
                  </nav>
                </div>
                <div class="col-md-4 text-center text-md-end">
                  <div class="d-flex align-items-center justify-content-center justify-content-md-end gap-3">
                    <small class="text-muted">© <script>document.write(new Date().getFullYear());</script> IDSMS</small>
                    <a href="#" class="btn btn-sm btn-outline-secondary">Contact</a>
                    <div class="d-none d-md-flex align-items-center gap-2">
                      <a href="#" class="text-muted"><i class="bx bxl-github fs-5"></i></a>
                      <a href="#" class="text-muted"><i class="bx bxl-twitter fs-5"></i></a>
                      <a href="#" class="text-muted"><i class="bx bxl-linkedin fs-5"></i></a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </footer>
          </div>
        </div>
      </div>
      
    </div>
    

    <script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('admin/assets/js/main.js') }}"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
  </body>
</html>
