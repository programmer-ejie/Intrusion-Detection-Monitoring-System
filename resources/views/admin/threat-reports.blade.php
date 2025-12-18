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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Intrusion Detection System | Threat Reports</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}" />

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

            <li class="menu-item">
              <a href="{{route('admin.system-status')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-server"></i>
                <div data-i18n="Analytics" class="fw-semibold">System Status</div>
              </a>
            </li>

            <li class="menu-item active">
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
          <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <span class="badge bg-primary">Threat Reports</span>
                </div>
              </div>

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="{{ asset('images/profile.jpeg') }}" alt class="w-px-40 h-auto rounded-circle" style="border:1px solid rgb(57,57,93)" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="{{ asset('images/profile.jpeg') }}" alt class="w-px-40 h-auto rounded-circle" style="border:1px solid rgb(57,57,93)" />
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
                    </li>
                    <li>
                      <a class="dropdown-item" href="{{ route('logout') }}">
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
              <div class="row mb-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header d-flex align-items-center">
                      <h5 class="mb-0">Threat Reports</h5>
                      <div class="ms-auto">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.threat-reports') }}?window=24h">Last 24h</a>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.threat-reports') }}?window=7d">7 Days</a>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.threat-reports') }}?window=30d">30 Days</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.threat-reports') }}?window=all">All</a>
                        <a class="btn btn-sm btn-success" href="{{ route('admin.threat-reports.export', request()->query()) }}">Export CSV</a>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="row mb-4">
                        <div class="col-md-3 mb-2">
                          <div class="p-3 bg-light rounded">Total Events<br><strong>{{ $total ?? 0 }}</strong></div>
                        </div>
                        <div class="col-md-3 mb-2">
                          <div class="p-3 bg-light rounded">Detected Attacks<br><strong class="text-danger">{{ $attacks ?? 0 }}</strong></div>
                        </div>
                        <div class="col-md-3 mb-2">
                          <div class="p-3 bg-light rounded">High Risk<br><strong class="text-warning">{{ $highRisk ?? 0 }}</strong></div>
                        </div>
                        <div class="col-md-3 mb-2">
                          <div class="p-3 bg-light rounded">Latest Event<br><strong>{{ optional($latest)->created_at ? optional($latest)->created_at->toDateTimeString() : '—' }}</strong></div>
                        </div>
                      </div>

                      <div class="table-responsive">
                        <table class="table table-striped">
                          <thead>
                            <tr>
                              <th>Timestamp</th>
                              <th>Src IP</th>
                              <th>Dst IP</th>
                              <th>Risk</th>
                              <th>Prob</th>
                              <th>Pkts</th>
                              <th>Bytes</th>
                            </tr>
                          </thead>
                          <tbody id="reports-tbody">
                            @foreach($rows as $r)
                            <tr>
                              <td>{{ $r->created_at }}</td>
                              <td>{{ $r->src_ip }}</td>
                              <td>{{ $r->dst_ip ?? $r->dst }}</td>
                              <td>{{ $r->risk_level }}</td>
                              <td>{{ $r->prob_attack }}</td>
                              <td>{{ $r->tot_fwd_pkts }}</td>
                              <td>{{ $r->flow_bytes_s }}</td>
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
          </div>
        </div>
      </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>

    <script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

    <script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>

    <script src="{{ asset('admin/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('admin/assets/js/main.js') }}"></script>
    

  </body>
</html>
