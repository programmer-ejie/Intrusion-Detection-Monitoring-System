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

    <title>Intrusion Detection System | Dashboard</title>

    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}" style = "border-radius: 50%;"/>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/boxicons.css') }}" />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('admin/assets/css/demo.css') }}" />

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/apex-charts/apex-charts.css') }}" />

    <script src="{{ asset('admin/assets/vendor/js/helpers.js') }}"></script>

    <script src="{{ asset('admin/assets/js/config.js') }}"></script>
    
    <style>
      @font-face {
        font-family: 'Cooper';
        src: url('{{ asset('fonts/Cooper.woff2') }}') format('woff2'), url('{{ asset('fonts/Cooper.woff') }}') format('woff');
        font-weight: 900;
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
            <a href="index.html" class="app-brand-link">
              <span class="app-brand-logo demo">
                 <img src="{{ asset('images/logo.png') }}" alt="logo" width="60px" height="50px"/>
              </span>
             <span class="app-brand-text demo menu-text fw-bolder text-uppercase cooper-brand" style = "color: rgb(88, 103, 143)">IDSMS</span>

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

            <li class="menu-item active">
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

            <li class="menu-item">
              <a href="{{route('admin.system-status')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-server"></i>
                <div data-i18n="Analytics" class="fw-semibold">System Status</div>
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
              <div class="row">
                <div class="col-lg-8 mb-4 order-0">
                  <div class="card">
                    <div class="d-flex align-items-end row">
                      <div class="col-sm-7">
                      <div class="card-body">
                            <h5 class="card-title text-primary">Intrusion Summary 🎯</h5>

                            <p class="mb-4">
                                The system detected 
                                <span id="attackCountSummary" class="fw-bold text-danger">{{ $attackCount }}</span> malicious
                                activities out of 
                                <span id="totalLogsSummary" class="fw-bold">{{ $totalLogs }}</span> network flows today
                                (<span id="attackRateSummary" class="fw-bold">{{ number_format($attackRate, 2) }}%</span> attack rate).
                            </p>

                            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary">
                                View Detailed Logs
                            </a>
                        </div>

                      </div>
                      <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                          <img
                            src="{{ asset('admin/assets/img/illustrations/man-with-laptop-light.png') }}"
                            height="140"
                            alt="View Badge User"
                            data-app-dark-img="{{ asset('admin/assets/img/illustrations/man-with-laptop-dark.png') }}"
                            data-app-light-img="{{ asset('admin/assets/img/illustrations/man-with-laptop-light.png') }}"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
               <div class="col-lg-4 col-md-4 order-1">
                    <div class="row">
                        <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                <i class="bx bx-bug text-danger" style="font-size:28px; line-height:1;"></i>
                                </div>
                                <div class="dropdown">
                                <button
                                    class="btn p-0"
                                    type="button"
                                    id="cardOpt3"
                                    data-bs-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                >
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">View More</a>
                                </div>
                                </div>
                            </div>

                            <span class="fw-semibold d-block mb-1">Detected Attacks</span>
                            <h3 id="attackCount" class="card-title mb-2 text-danger">{{ $attackCount ?? 0 }}</h3>
                           
                            </div>
                        </div>
                        </div>

                        <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                               <i class="bx bx-check-shield text-success" style="font-size:28px; line-height:1;"></i>
                                </div>
                                <div class="dropdown">
                                <button
                                    class="btn p-0"
                                    type="button"
                                    id="cardOpt6"
                                    data-bs-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                >
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">View More</a>
                                </div>
                                </div>
                            </div>

                              <span class="fw-semibold d-block mb-1">Normal Traffic</span>
                            <h3 id="benignCount" class="card-title text-nowrap mb-1 text-success">{{ $benignCount ?? 0 }}</h3>
                           
                            </div>
                        </div>
                        </div>
                    </div>
                    </div>

           
              <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
  <div class="card">
    <div class="row row-bordered g-0">

      <div class="col-md-8">
        <h5 class="card-header m-0 me-2 pb-3">Network Traffic Overview</h5>

        <div id="totalRevenueChart" class="px-2"></div>

        <div class="px-4 pb-3 text-muted small">
          Showing benign vs detected attacks within the selected time window.
        </div>
      </div>

      <div class="col-md-4">
        <div class="card-body">
          <div class="text-center">
           <div class="dropdown">
                <button
                    class="btn btn-sm btn-outline-primary dropdown-toggle"
                    type="button"
                    id="growthReportId"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    {{ $selectedWindowLabel ?? 'All Time' }}
                </button>

                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="growthReportId">
                    <a class="dropdown-item" href="{{ url()->current() }}?window=24h">Last 24 Hours</a>
                    <a class="dropdown-item" href="{{ url()->current() }}?window=7d">Last 7 Days</a>
                    <a class="dropdown-item" href="{{ url()->current() }}?window=30d">Last 30 Days</a>
                    <a class="dropdown-item" href="{{ url()->current() }}?window=all">All Time</a>
                </div>
                </div>
          </div>
        </div>

        <div id="growthChart"></div>

        <div class="text-center fw-semibold pt-3 mb-2">
          <span id="attackRate" >{{ number_format($attackRate ?? 0, 2) }}%</span> Attack Rate
        </div>

      <div class="d-flex px-xxl-4 px-lg-2 p-4 justify-content-center">
  <div class="w-100 text-center bg-light p-3 rounded shadow-sm">
    <ul class="list-unstyled mb-0 small">
      <li class="mb-1">
        <i class="bx bx-info-circle text-primary me-2"></i>
        Hover over charts to see exact values and timestamps.
      </li>
    </ul>
  </div>
</div>
      </div>

    </div>
  </div>
</div>

                <div class="col-12 col-md-8 col-lg-4 order-3 order-md-2">
                  <div class="row">
                    <div class="col-6 mb-4">
                      <div class="card">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                             <i class="bx bx-broadcast text-primary" style="font-size:28px; line-height:1;"></i>
                            </div>
                            <div class="dropdown">
                              <button
                                class="btn p-0"
                                type="button"
                                id="cardOpt4"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                              >
                                <i class="bx bx-dots-vertical-rounded"></i>
                              </button>
                              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                              </div>
                            </div>
                          </div>
                          <span class="d-block mb-1">Total Flows</span>
                          <h3 id="totalLogs" class="card-title text-nowrap mb-2">{{ $totalLogs }}</h3>
                          <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i>Packets</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-6 mb-4">
                      <div class="card">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                              <i class="bx bx-down-arrow-alt text-danger" style="font-size:28px; line-height:1;"></i>
                            </div>
                            <div class="dropdown">
                              <button
                                class="btn p-0"
                                type="button"
                                id="cardOpt1"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                              >
                                <i class="bx bx-dots-vertical-rounded"></i>
                              </button>
                              <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                              </div>
                            </div>
                          </div>
                          <span class="fw-semibold d-block mb-1">Attack Rate</span>
                          <h3 class="card-title mb-2">{{ number_format($attackRate, 2) }}%</h3>
                          <small class="text-danger fw-semibold"><i class="bx bx-down-arrow-alt"></i> Malicious</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 mb-4">
                      <div class="card">
                        <div class="card-body">
                          <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 align-items-start">
                            <div class="d-flex flex-column">
                              <div class="card-title">
                                <h5 class="text-nowrap mb-2">Attack Distribution</h5>
                              </div>
                              <div class="mt-2">
                                <div class="h4 text-danger fw-semibold mb-0"><i class="bx bx-alert me-2"></i> {{ $attackCount }} Detected</div>
                              </div>
                            </div>
                            <div id="profileReportChart" class="mt-3 mt-sm-0"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-4">
                  <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between pb-0">
                      <div class="card-title mb-0">
                        <h5 class="m-0 me-2">Attack Types</h5>
                        <small class="text-muted">Top 3</small>
                      </div>
                      <div class="dropdown">
                        <button
                          class="btn p-0"
                          type="button"
                          id="attackTypes"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false"
                        >
                          <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="attackTypes">
                          <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                          <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                          <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        </div>
                      </div>
                    </div>
                    <div class="card-body">
                      <div id="attackTypeChart"></div>
                      @php
                        $attackLabels = $attackByType['labels'] ?? [];
                        $attackData = $attackByType['data'] ?? [];
                        $attackTotal = array_sum($attackData ?: [0]);
                        $attackColors = ['#f44336', '#ff9800', '#2196f3', '#9c27b0', '#4caf50'];
                      @endphp
                      <ul class="p-0 m-0 small">
                        @foreach(collect($attackLabels)->take(3)->values() as $i => $type)
                        @php
                          $count = $attackData[$i] ?? 0;
                          $percent = $attackTotal ? round(($count / $attackTotal) * 100, 1) : 0;
                          $color = $attackColors[$i % count($attackColors)];
                        @endphp
                        <li class="mb-2">
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-1">
                              <span style="width:8px;height:8px;border-radius:2px;background:{{ $color }};display:inline-block;"></span>
                              <strong class="me-1 small">{{ $type ?? 'Unknown' }}</strong>
                              <small class="text-muted">{{ $percent }}%</small>
                            </div>
                            <span class="badge bg-light text-dark small py-1 px-2">{{ number_format($count) }}</span>
                          </div>
                          <div class="progress" style="height:6px; background:#f1f3f5; border-radius:6px;">
                            <div class="progress-bar" role="progressbar" style="width:{{ $percent }}%; background:{{ $color }}; border-radius:6px;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                          </div>
                        </li>
                        @endforeach
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-4 order-1 mb-4">
                  <div class="card h-100">
                    <div class="card-header">
                      <h5 class="m-0">Risk Level Distribution</h5>
                      <small class="text-muted">Current Period</small>
                    </div>
                    <div class="card-body px-0">
                      <div id="riskLevelChart"></div>
                      @php
                        $rlLabels = $riskLevelDistribution['labels'] ?? [];
                        $rlData = $riskLevelDistribution['data'] ?? [];
                        $rlTotal = array_sum($rlData ?: [0]);
                        $rlColors = ['#dc3545', '#28a745', '#ffc107'];
                      @endphp
                      <div class="d-flex justify-content-center pt-4 gap-3 flex-wrap">
                        @foreach($rlLabels as $index => $level)
                        @php
                          $val = $rlData[$index] ?? 0;
                          $pct = $rlTotal ? round(($val / $rlTotal) * 100, 1) : 0;
                          $dot = $rlColors[$index % count($rlColors)];
                        @endphp
                        <div class="text-center" style="min-width:100px;">
                          <div class="d-flex align-items-center justify-content-center mb-1">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $dot }};display:inline-block;margin-right:8px;"></span>
                            <p class="mb-0 text-capitalize small">{{ $level }}</p>
                          </div>
                          <h6 class="mb-0 fw-semibold">{{ $val }} <small class="text-muted">({{ $pct }}%)</small></h6>
                        </div>
                        @endforeach
                      </div>

                      @php
                        // top risk label
                        $topLabel = 'N/A';
                        if (!empty($rlData) && $rlTotal > 0) {
                            $max = max($rlData);
                            $topIdx = array_search($max, $rlData);
                            $topLabel = ($topIdx !== false && isset($rlLabels[$topIdx])) ? $rlLabels[$topIdx] : 'N/A';
                        }
                        // top attacked IP if available
                        $topIp = collect($topAttackedIPs ?? [])->first()->dst_ip ?? 'N/A';
                        // recent attacks from chart (last bucket)
                        $recentAttacks = 0;
                        if (isset($chartData['attacks']) && is_array($chartData['attacks']) && count($chartData['attacks'])>0) {
                            $recentAttacks = end($chartData['attacks']);
                        }
                      @endphp

                      <div class="border-top mt-3 pt-3 px-3">
                        <div class="d-flex justify-content-between align-items-center small text-muted">
                          <div>
                            <small class="d-block">Top Level</small>
                            <div class="fw-semibold">{{ ucwords($topLabel) }}</div>
                          </div>
                          <div class="text-center">
                            <small class="d-block">Top IP</small>
                            <div class="fw-semibold">{{ $topIp }}</div>
                          </div>
                          <div class="text-end">
                            <small class="d-block">Recent</small>
                            <div class="fw-semibold">{{ number_format($recentAttacks) }}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-4 order-2 mb-4">
                  <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                      <h5 class="card-title m-0 me-2">Top Attacked IPs</h5>
                      <div class="dropdown">
                        <button
                          class="btn p-0"
                          type="button"
                          id="topIPs"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false"
                        >
                          <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="topIPs">
                          <a class="dropdown-item" href="javascript:void(0);">Last 24 Hours</a>
                          <a class="dropdown-item" href="javascript:void(0);">Last Month</a>
                          <a class="dropdown-item" href="javascript:void(0);">Last Year</a>
                        </div>
                      </div>
                    </div>
                    <div class="card-body">
                      <ul class="p-0 m-0">
                        @forelse(collect($topAttackedIPs)->take(5) as $ip)
                        <li class="d-flex mb-4 pb-1">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                              <i class="bx bx-broadcast"></i>
                            </span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="text-muted d-block mb-1">Destination IP</small>
                              <h6 class="mb-0">{{ $ip->dst_ip }}</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-1">
                              <h6 class="mb-0">{{ $ip->count }}</h6>
                              <span class="text-muted">attacks</span>
                            </div>
                          </div>
                        </li>
                        @empty
                        <li class="text-center text-muted py-3">No attack data available</li>
                        @endforelse
                      </ul>
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
                        <img src="{{ asset('images/logo.png') }}" alt="IDSMS" style="width:36px;height:36px;border-radius:6px;object-fit:cover"/>
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

            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>

      <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

    <script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>

    <script src="{{ asset('admin/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('admin/assets/js/main.js') }}"></script>

    <script>
    /* Styles for adaptive number sizes */
    const style = document.createElement('style');
    style.innerHTML = `
      h3[id="attackCount"], h3[id="benignCount"], h3[id="totalLogs"] {
        transition: font-size .12s ease;
      }
      h3.num-medium { font-size: 1.2rem !important; }
      h3.num-small { font-size: 1rem !important; }
    `;
    document.head.appendChild(style);

        const chartData = @json($chartData);
        const attackRate = {{ $attackRate }};
        const attackByType = @json($attackByType);
        const riskLevelDist = @json($riskLevelDistribution);

        if (document.getElementById('totalRevenueChart')) {
            const totalRevenueChart = new ApexCharts(
                document.getElementById('totalRevenueChart'),
                {
                    series: [
                        {
                            name: 'Benign Traffic',
                            data: chartData.benign,
                            color: '#28a745'
                        },
                        {
                            name: 'Detected Attacks',
                            data: chartData.attacks,
                            color: '#dc3545'
                        }
                    ],
                    chart: {
                        height: 300,
                        type: 'area',
                        toolbar: { show: false },
                        sparkline: { enabled: false }
                    },
                    colors: ['#28a745', '#dc3545'],
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient' },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: chartData.labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: { show: true },
                    grid: { show: true, borderColor: '#f0f0f0' },
                    tooltip: {
                        theme: 'light',
                        x: { show: true },
                        y: { formatter: (val) => Math.round(val) }
                    }
                }
            );
            totalRevenueChart.render();
        }

        if (document.getElementById('growthChart')) {
            const growthChart = new ApexCharts(
                document.getElementById('growthChart'),
                {
                    series: [Math.round(attackRate)],
                    chart: {
                        height: 200,
                        type: 'radialBar'
                    },
                    colors: ['#ff4757'],
                    plotOptions: {
                        radialBar: {
                            hollow: { size: '70%' },
                            dataLabels: {
                                name: { show: false },
                                value: {
                                    fontSize: '18px',
                                    color: '#333',
                                    offsetY: 10
                                }
                            }
                        }
                    },
                    stroke: { lineCap: 'round' }
                }
            );
            growthChart.render();
        }

        if (document.getElementById('attackTypeChart')) {
            const attackTypeChart = new ApexCharts(
                document.getElementById('attackTypeChart'),
                {
                    series: [attackByType.data],
                    chart: {
                        height: 250,
                        type: 'bar',
                        toolbar: { show: false }
                    },
                    colors: ['#696cff'],
                    plotOptions: {
                        bar: {
                            columnWidth: '50%',
                            distributed: true
                        }
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: attackByType.labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { show: false }
                    },
                    yaxis: { show: true }
                }
            );
            attackTypeChart.render();
            // keep reference for live updates
            window.attackTypeChart = attackTypeChart;
        }

        if (document.getElementById('riskLevelChart')) {
            const riskLevelChart = new ApexCharts(
                document.getElementById('riskLevelChart'),
                {
                    series: riskLevelDist.data,
                    chart: {
                        height: 250,
                        type: 'donut'
                    },
                    colors: ['#dc3545', '#28a745', '#ffc107'],
                    labels: riskLevelDist.labels,
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        fontSize: '12px'
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '14px'
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: { enabled: false }
                }
            );
            riskLevelChart.render();
            window.riskLevelChart = riskLevelChart;
        }

        if (document.getElementById('profileReportChart')) {
            const profileReportChart = new ApexCharts(
                document.getElementById('profileReportChart'),
                {
                    series: [Math.round({{ $totalLogs ? ($attackCount / $totalLogs * 100) : 0 }})],
                    chart: {
                        height: 120,
                        type: 'radialBar'
                    },
                    colors: ['#ff4757'],
                    plotOptions: {
                        radialBar: {
                            hollow: { size: '50%' },
                            dataLabels: { show: false }
                        }
                    }
                }
            );
            profileReportChart.render();
            window.profileReportChart = profileReportChart;
        }
    </script>

    <script>
    /* Number formatting and adaptive size for dashboard counters */
    function abbreviateNumber(value) {
      if (value === null || value === undefined) return '0';
      const num = Number(value);
      if (isNaN(num)) return String(value);
      const abs = Math.abs(num);
      if (abs >= 1e12) return (num / 1e12).toFixed(2).replace(/\.00$/, '') + 'T';
      if (abs >= 1e9) return (num / 1e9).toFixed(2).replace(/\.00$/, '') + 'B';
      if (abs >= 1e6) return (num / 1e6).toFixed(2).replace(/\.00$/, '') + 'M';
      if (abs >= 1e3) return (num / 1e3).toFixed(2).replace(/\.00$/, '') + 'K';
      return num.toString();
    }

    function adjustNumberClass(el, num) {
      if (!el) return;
      el.classList.remove('num-medium', 'num-small');
      const abs = Math.abs(Number(num) || 0);
      if (abs >= 1e9) el.classList.add('num-small');
      else if (abs >= 1e6) el.classList.add('num-medium');
    }

    function safeSetText(id, value, opts = {abbrev: true}) {
      const el = document.getElementById(id);
      if (!el) return;
      if (value === null || value === undefined) {
        el.innerText = '0';
        el.classList.remove('num-medium', 'num-small');
        return;
      }
      // keep percent values as-is
      if (String(value).includes('%') || id === 'attackRate' || opts.raw === true) {
        el.innerText = String(value);
        return;
      }
      if (opts.abbrev) {
        const num = Number(value);
        if (!isNaN(num)) {
          el.innerText = abbreviateNumber(num);
          adjustNumberClass(el, num);
          return;
        }
      }
      el.innerText = String(value);
    }

    // format existing server-rendered values on page load
    function formatInitialNumbers() {
      ['totalLogs', 'attackCount', 'benignCount'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const raw = el.innerText.replace(/[, ]/g, '') || '0';
        safeSetText(id, Number(raw));
      });
      const ar = document.getElementById('attackRate');
      if (ar) {
        // ensure percent formatting (already has % in server render)
        ar.innerText = ar.innerText.trim();
      }
    }

    async function fetchData() {
        try {
            // Get the current window parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const windowParam = urlParams.get('window') || 'all';
            
            // avoid cached responses by adding a timestamp and disabling cache
            const res = await fetch(`/dashboard/refresh?window=${windowParam}&_=${Date.now()}`, { cache: 'no-store' });
            if (!res.ok) {
                console.error('refresh endpoint returned', res.status);
                return;
            }
            const data = await res.json();
            console.log('dashboard refresh', data);

            // update numeric values in the page
            safeSetText('totalLogs', data.totalLogs);
            safeSetText('attackCount', data.attackCount);
            safeSetText('benignCount', data.benignCount);

            // update summary inline values
            safeSetText('attackCountSummary', data.attackCount);
            safeSetText('totalLogsSummary', data.totalLogs);
            safeSetText('attackRateSummary', (data.attackRate !== undefined) ? (parseFloat(data.attackRate).toFixed(2) + '%') : '0%');
            safeSetText('attackRate', (data.attackRate !== undefined) ? (parseFloat(data.attackRate).toFixed(2) + '%') : '0%');

            // Update charts if present and API returned data
            try {
                if (window.attackTypeChart && data.attackByType) {
                    if (data.attackByType.labels) {
                        window.attackTypeChart.updateOptions({ xaxis: { categories: data.attackByType.labels } });
                    }
                    if (data.attackByType.data) {
                        window.attackTypeChart.updateSeries([{ data: data.attackByType.data }]);
                    }
                }

                if (window.riskLevelChart && data.riskLevelDistribution) {
                    if (data.riskLevelDistribution.labels) {
                        window.riskLevelChart.updateOptions({ labels: data.riskLevelDistribution.labels });
                    }
                    if (data.riskLevelDistribution.data) {
                        window.riskLevelChart.updateSeries(data.riskLevelDistribution.data);
                    }
                }

                if (window.profileReportChart && data.totalLogs !== undefined) {
                    const percent = data.totalLogs ? Math.round((data.attackCount / data.totalLogs) * 100) : 0;
                    window.profileReportChart.updateSeries([percent]);
                }

                if (window.totalRevenueChart && data.chartData) {
                    // totalRevenueChart was local variable earlier; attempt safe update if it exists
                    if (window.totalRevenueChart && typeof window.totalRevenueChart.updateSeries === 'function') {
                        window.totalRevenueChart.updateOptions({ xaxis: { categories: data.chartData.labels } });
                        window.totalRevenueChart.updateSeries([
                            { name: 'Benign Traffic', data: data.chartData.benign },
                            { name: 'Detected Attacks', data: data.chartData.attacks }
                        ]);
                    }
                }

                // Update Attack Types list
                if (data.attackByType) {
                    const attackLabels = data.attackByType.labels || [];
                    const attackData = data.attackByType.data || [];
                    const attackTotal = attackData.reduce((a, b) => a + b, 0);
                    const attackColors = ['#f44336', '#ff9800', '#2196f3', '#9c27b0', '#4caf50'];
                    const attackListHtml = attackLabels.slice(0, 3).map((type, i) => {
                        const count = attackData[i] || 0;
                        const percent = attackTotal ? Math.round((count / attackTotal) * 100) : 0;
                        const color = attackColors[i % attackColors.length];
                        return `<li class="mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-1">
                                    <span style="width:8px;height:8px;border-radius:2px;background:${color};display:inline-block;"></span>
                                    <strong class="me-1 small">${type || 'Unknown'}</strong>
                                    <small class="text-muted">${percent}%</small>
                                </div>
                                <span class="badge bg-light text-dark small py-1 px-2">${count.toLocaleString()}</span>
                            </div>
                            <div class="progress" style="height:6px; background:#f1f3f5; border-radius:6px;">
                                <div class="progress-bar" role="progressbar" style="width:${percent}%; background:${color}; border-radius:6px;" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </li>`;
                    }).join('');
                    const attackListEl = document.querySelector('#attackTypeChart').parentElement.querySelector('ul');
                    if (attackListEl) attackListEl.innerHTML = attackListHtml;
                }

                // Update Risk Level Distribution
                if (data.riskLevelDistribution) {
                    const rlLabels = data.riskLevelDistribution.labels || [];
                    const rlData = data.riskLevelDistribution.data || [];
                    const rlTotal = rlData.reduce((a, b) => a + b, 0);
                    const rlColors = ['#dc3545', '#28a745', '#ffc107'];
                    
                    // Update risk level pills
                    const riskPillsHtml = rlLabels.map((level, index) => {
                        const val = rlData[index] || 0;
                        const pct = rlTotal ? Math.round((val / rlTotal) * 100) : 0;
                        const dot = rlColors[index % rlColors.length];
                        return `<div class="text-center" style="min-width:100px;">
                            <div class="d-flex align-items-center justify-content-center mb-1">
                                <span style="width:10px;height:10px;border-radius:50%;background:${dot};display:inline-block;margin-right:8px;"></span>
                                <p class="mb-0 text-capitalize small">${level}</p>
                            </div>
                            <h6 class="mb-0 fw-semibold">${abbreviateNumber(val)} <small class="text-muted">(${pct}%)</small></h6>
                        </div>`;
                    }).join('');
                    const riskPillsEl = document.querySelector('#riskLevelChart').parentElement.querySelector('.d-flex.justify-content-center');
                    if (riskPillsEl) riskPillsEl.innerHTML = riskPillsHtml;
                    
                    // Update bottom risk level stats
                    let topLabel = 'N/A';
                    if (rlData.length > 0 && rlTotal > 0) {
                        const maxIdx = rlData.indexOf(Math.max(...rlData));
                        if (maxIdx !== -1 && rlLabels[maxIdx]) {
                            topLabel = rlLabels[maxIdx].charAt(0).toUpperCase() + rlLabels[maxIdx].slice(1);
                        }
                    }
                    const topLevelEl = document.querySelector('#riskLevelChart').parentElement.querySelector('.border-top');
                    if (topLevelEl) {
                        const topIp = data.topAttackedIPs && data.topAttackedIPs.length > 0 ? data.topAttackedIPs[0].dst_ip : 'N/A';
                        const recentAttacks = data.chartData && data.chartData.attacks && data.chartData.attacks.length > 0 ? data.chartData.attacks[data.chartData.attacks.length - 1] : 0;
                        topLevelEl.innerHTML = `<div class="d-flex justify-content-between align-items-center small text-muted">
                            <div>
                                <small class="d-block">Top Level</small>
                                <div class="fw-semibold">${topLabel}</div>
                            </div>
                            <div class="text-center">
                                <small class="d-block">Top IP</small>
                                <div class="fw-semibold">${topIp}</div>
                            </div>
                            <div class="text-end">
                                <small class="d-block">Recent</small>
                                <div class="fw-semibold">${abbreviateNumber(recentAttacks)}</div>
                            </div>
                        </div>`;
                    }
                }

                // Update Top Attacked IPs list
                if (data.topAttackedIPs && Array.isArray(data.topAttackedIPs)) {
                    const topIpsHtml = data.topAttackedIPs.slice(0, 5).map(ip => `<li class="d-flex mb-4 pb-1">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-broadcast"></i>
                            </span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <small class="text-muted d-block mb-1">Destination IP</small>
                                <h6 class="mb-0">${ip.dst_ip}</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-1">
                                <h6 class="mb-0">${ip.count}</h6>
                                <span class="text-muted">attacks</span>
                            </div>
                        </div>
                    </li>`).join('');
                    const topIpsListEl = document.querySelector('#topIPs').closest('.card').querySelector('ul');
                    if (topIpsListEl) {
                        topIpsListEl.innerHTML = topIpsHtml || '<li class="text-center text-muted py-3">No attack data available</li>';
                    }
                }
            } catch (chartErr) {
                console.warn('chart update error', chartErr);
            }

        } catch (err) {
            console.error('Error fetching dashboard data:', err);
        }
    }

    // start polling after DOM ready: initial fetch + interval
    document.addEventListener('DOMContentLoaded', function () {
      // format initial server-rendered counters then start polling
      try { formatInitialNumbers(); } catch(e) { /* ignore */ }
      fetchData();
      setInterval(fetchData, 5000);
    });
</script>
  </body>
</html>
