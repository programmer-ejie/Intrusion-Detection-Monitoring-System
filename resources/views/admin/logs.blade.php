<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('admin/assets') }}/" data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Intrusion Detection System | Network Logs</title>
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
            <li class="menu-item active">
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
              <div class="card">
                <div class="card-body">
                  <form id="logs-filter-form" method="GET" action="{{ route('admin.logs') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                      <label for="date" class="form-label small">Date</label>
                      <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control" />
                    </div>
                    <div class="col-md-3">
                      <label for="type" class="form-label small">Type</label>
                      <select id="type" name="type" class="form-select">
                        <option value="" {{ request('type') == '' ? 'selected' : '' }}>All</option>
                        <option value="normal" {{ in_array(request('type'), ['normal','benign'], true) ? 'selected' : '' }}>Normal</option>
                        <option value="attack" {{ request('type') == 'attack' ? 'selected' : '' }}>Attack</option>
                      </select>
                    </div>
                    {{-- <div class="col-md-4">
                      <label for="q" class="form-label small">Search</label>
                      <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="src/dst/protocol/notes..." />
                    </div> --}}
                  <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('admin.logs') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                  </form>
             
                </div>
                <div class="card-header" style = "font-weight: bolder;">Network Logs</div>
                <div class="table-responsive text-nowrap">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Timestamp</th>
                        <th>Src IP</th>
                        <th>Dst IP</th>
                        <th>Protocol</th>
                        <th>Type</th>
                        @if($hasNotes)
                        <th>Notes</th>
                        @endif
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($logs as $log)
                      @php
                        $displayType = 'Normal';
                        $isAttack = false;
                        if ($typeColumn) {
                          if ($typeColumn === 'is_malicious') {
                            $isAttack = (bool) ($log->{$typeColumn});
                            $displayType = $isAttack ? 'Attack' : 'Normal';
                          } else {
                            $val = strtolower((string) ($log->{$typeColumn} ?? ''));
                            if ($val === 'benign' || $val === 'normal' || $val === '') {
                              $displayType = 'Normal';
                              $isAttack = false;
                            } else {
                              $displayType = ucfirst($val);
                              $isAttack = true;
                            }
                          }
                        } else {
                          $displayType = 'Normal';
                          $isAttack = false;
                        }
                        $typeClass = $isAttack ? 'text-danger' : 'text-success';
                      @endphp
                      <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ optional($log->created_at)->toDateTimeString() ?? '-' }}</td>
                        <td>{{ $log->src_ip ?? '-' }}</td>
                        <td>{{ $log->dst_ip ?? '-' }}</td>
                        <td>{{ $log->protocol ?? '-' }}</td>
                        <td class="{{ $typeClass }}">{{ $displayType }}</td>
                        @if($hasNotes)
                        <td>{{ $log->notes ?? '-' }}</td>
                        @endif
                      </tr>
                      @empty
                      <tr>
                        <td colspan="{{ $hasNotes ? 7 : 6 }}" class="text-center text-muted">No logs found</td>
                      </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
                <div class="card-footer d-flex justify-content-center">
                  {{ $logs->links('pagination::bootstrap-5') }}
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
    <script>
      (function () {
        var navbarSearch = document.getElementById('navbar-search');
        var qInput = document.getElementById('q');
        var dateInput = document.getElementById('date');
        var typeInput = document.getElementById('type');
        if (navbarSearch && qInput) {
          navbarSearch.value = qInput.value || '';
          qInput.addEventListener('input', function () { navbarSearch.value = qInput.value; });
          navbarSearch.addEventListener('input', function () { qInput.value = navbarSearch.value; });
          navbarSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
              e.preventDefault();
              var params = new URLSearchParams(window.location.search);
              if (navbarSearch.value) params.set('q', navbarSearch.value); else params.delete('q');
              if (dateInput && dateInput.value) params.set('date', dateInput.value); else params.delete('date');
              if (typeInput && typeInput.value) params.set('type', typeInput.value); else params.delete('type');
              window.location.pathname = "{{ route('admin.logs') }}";
              window.location.search = params.toString();
            }
          });
        }
      })();
    </script>
  </body>
</html>