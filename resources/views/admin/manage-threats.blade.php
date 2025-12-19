<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('admin/assets') }}/" data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Intrusion Detection System | Manage Threats</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}" style="border-radius: 50%;" />
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
      .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
      }
      .status-blocked {
        background-color: #fff5f5;
        color: #d32f2f;
      }
      .status-resolved {
        background-color: #f0f7ff;
        color: #1976d2;
      }
      .status-unresolved {
        background-color: #fff3e0;
        color: #f57c00;
      }
      @media (max-width: 576px) {
        .btn-text-hide {
          font-size: 0;
          padding: 0.375rem 0.5rem;
        }
        .btn-text-hide i {
          font-size: 1rem;
          margin: 0;
        }
        .modal-header .modal-title {
          font-size: 1.1rem;
        }
        .modal-body {
          font-size: 0.9rem;
        }
        .modal-footer {
          font-size: 0.85rem;
        }
      }
      .modal-body {
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
      }

      /* Ensure modal fits content on small screens and prevents overlap */
      @media (max-width: 576px) {
        .modal-dialog { max-width: 95% !important; margin: 1.75rem auto; }
        .modal-content { overflow: visible; }
        .modal-header, .modal-footer { flex-wrap: wrap; gap: 0.5rem; }
        .modal-footer .btn { flex: 1 1 auto; min-width: 80px; }
        .modal-body p { margin-bottom: 0.5rem; }
      }

      /* Center columns for Risk Level, Status, and Actions */
      td:nth-child(7), th:nth-child(7) {
        text-align: center;
      }
      
      td:nth-child(8), th:nth-child(8) {
        text-align: center;
      }
      
      td:last-child, th:last-child {
        text-align: center;
      }

      /* Hide Actions column and header when blocked status is selected */
      .status-blocked-filter td:last-child,
      .status-blocked-filter th:last-child {
        display: none;
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
                <img src="{{ asset('images/logo.png') }}" alt="logo" width="60px" height="50px" />
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
              <a href="{{ route('admin.dashboard') }}" class="menu-link">
                 <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics" class="fw-semibold">Dashboard</div>
              </a>
            </li>
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">FUNCTIONS</span>
            </li>
            <li class="menu-item">
              <a href="{{ route('admin.logs') }}" class="menu-link">
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
          <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>
            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <i class="bx bx-search fs-4 lh-0"></i>
                  <input id="navbar-search" type="text" class="form-control border-0 shadow-none" placeholder="src/dst/protocol/notes..." aria-label="Search..." />
                </div>
              </div>
              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="{{ asset('images/profile.jpeg') }}" alt class="w-px-40 h-auto rounded-circle" style="border: 1px solid rgb(57, 57, 93)" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="{{ asset('images/profile.jpeg') }}" alt class="w-px-40 h-auto rounded-circle" style="border: 1px solid rgb(57, 57, 93)" />
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
            <div class="container-xxl flex-grow-1 container-p-y" style="padding-top: 0;">
              @if($message = session('success'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              @endif

              @if($message = session('error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              @endif

              <div class="card">
                <div class="card-body">
                  <form id="threats-filter-form" method="GET" action="{{ route('admin.manage-threats') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                      <label for="date" class="form-label small">Date</label>
                      <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control" />
                    </div>
                    <div class="col-md-3">
                      <label for="status" class="form-label small">Status</label>
                      <select id="status" name="status" class="form-select">
                        <option value="unresolved" {{ request('status') == 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                        <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
                      </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                      <button type="submit" class="btn btn-primary">Filter</button>
                      <a href="{{ route('admin.manage-threats') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                  </form>
                </div>
                <div class="card-header" style="font-weight: bolder;">Active Threats</div>
                <div class="table-responsive text-nowrap">
                  <table class="table table-striped {{ request('status') == 'blocked' ? 'status-blocked-filter' : '' }}">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Timestamp</th>
                        <th>Src IP</th>
                        <th>Dst IP</th>
                        <th>Protocol</th>
                        <th>Type</th>
                        <th>Risk Level</th>
                        <th>Status</th>
                        @if($hasNotes)
                        <th>Notes</th>
                        @endif
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($threats as $threat)
                      @php
                        $displayType = 'Threat';
                        if ($typeColumn) {
                          if ($typeColumn === 'is_malicious') {
                            $displayType = 'Attack';
                          } else {
                            $val = strtolower((string) ($threat->{$typeColumn} ?? ''));
                            $displayType = !empty($val) ? ucfirst($val) : 'Threat';
                          }
                        }
                        
                        $statusBadgeClass = 'status-unresolved';
                        $statusText = 'Unresolved';
                        if ($threat->status === 'blocked') {
                          $statusBadgeClass = 'status-blocked';
                          $statusText = 'Blocked';
                        } elseif ($threat->status === 'resolved') {
                          $statusBadgeClass = 'status-resolved';
                          $statusText = 'Resolved';
                        }
                      @endphp
                      <tr>
                        <td>{{ $threat->id }}</td>
                        <td>{{ optional($threat->created_at)->toDateTimeString() ?? '-' }}</td>
                        <td><strong>{{ $threat->src_ip ?? '-' }}</strong></td>
                        <td>{{ $threat->dst_ip ?? '-' }}</td>
                        <td>{{ $threat->protocol ?? '-' }}</td>
                        <td class="text-danger">{{ $displayType }}</td>
                        <td>
                          <span class="badge bg-danger">{{ $threat->risk_level ?? 'Unknown' }}</span>
                        </td>
                        <td>
                          <span class="status-badge {{ $statusBadgeClass }}">{{ $statusText }}</span>
                        </td>
                        @if($hasNotes)
                        <td>{{ $threat->notes ?? '-' }}</td>
                        @endif
                        <td>
                          @if($threat->status !== 'blocked' && $threat->status !== 'resolved')
                          <div class="d-flex">
                            <button type="button" class="btn btn-sm btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#blockModal{{ $threat->id }}">
                              <i class="bx bx-block"></i>
                              <span class="d-none d-sm-inline"> Block</span>
                            </button>
                          </div>

                          <!-- Block Modal -->
                          <div class="modal fade" id="blockModal{{ $threat->id }}" tabindex="-1" aria-labelledby="blockModalLabel{{ $threat->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="blockModalLabel{{ $threat->id }}">Block IP Address</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                  <p>Are you sure you want to <strong>block</strong> IP address <strong class="text-danger">{{ $threat->src_ip }}</strong>?</p>
                                  <p class="text-muted mb-0">This will mark all threats from this IP as blocked.</p>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-danger btn-sm btn-text-hide" data-bs-dismiss="modal">
                                    <i class="bx bx-x"></i><span class="d-none d-sm-inline"> Cancel</span>
                                  </button>
                                  <form method="POST" action="{{ route('admin.threat.block', $threat->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm btn-text-hide">
                                      <i class="bx bx-check"></i><span class="d-none d-sm-inline"> Block IP</span>
                                    </button>
                                  </form>
                                </div>
                              </div>
                            </div>
                          </div>
                          @else
                          <span class="text-muted">-</span>
                          @endif
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="{{ $hasNotes ? 10 : 9 }}" class="text-center text-muted">No threats found</td>
                      </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
                <div class="card-footer d-flex justify-content-center">
                  {{ $threats->links('pagination::bootstrap-5') }}
                </div>
              </div>
            </div>
            <div class="content-backdrop fade"></div>
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
    <script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('admin/assets/js/main.js') }}"></script>
  </body>
</html>
