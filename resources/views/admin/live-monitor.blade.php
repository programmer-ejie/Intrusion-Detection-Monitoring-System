<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('admin/assets') }}/" data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Intrusion Detection System | Live Monitor</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/apex-charts/apex-charts.css') }}" />

    <script src="{{ asset('admin/assets/vendor/js/helpers.js') }}"></script>

    <script src="{{ asset('admin/assets/js/config.js') }}"></script>
    <style>
      /* Mobile-friendly tweaks for Live Monitor */
      @media (max-width: 576px) {
        #flows-chart { height: 180px !important; }
        .live-metric { padding: .6rem !important; font-size: .95rem; }
        .card .card-header h5 { font-size: 1rem; }
        .menu-inner .menu-link div { font-size: .95rem; }
      }
      @media (min-width: 577px) {
        #flows-chart { height: 250px; }
      }
      /* ensure metric badges wrap nicely */
      .metric-box { display: flex; flex-direction: column; gap: .25rem; }
      @media (min-width: 576px) { .metric-box { flex-direction: row; align-items: center; justify-content: space-between; } }
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

            <li class="menu-item">
              <a href="{{route('admin.threat-reports')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                <div data-i18n="Analytics" class="fw-semibold">Threat Reports</div>
              </a>
            </li>

            <li class="menu-item active">
              <a href="{{route('admin.live')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-tv"></i>
                <div data-i18n="Analytics" class="fw-semibold">Live Monitor</div>
              </a>
            </li>

            </li>

        
              </ul>
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
                  <span class="badge bg-primary">Network Monitor</span>
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
              <div class="row mb-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header"><h5 class="mb-0">Live Monitor</h5></div>
                    <div class="card-body">
                      <p class="mb-2">Status: <strong>{{ $placeholder['status'] ?? 'Connected' }}</strong></p>
                      <p class="mb-2">Last update: <strong id="last-update">{{ $placeholder['last_update'] }}</strong></p>
                      <div id="live-metrics" class="row">
                        <div class="col-12 col-md-4 mb-3">
                          <div class="p-3 bg-light rounded metric-box live-metric">
                            <div class="text-muted">Incoming Flows</div>
                            <strong id="flows">--</strong>
                          </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                          <div class="p-3 bg-light rounded metric-box live-metric">
                            <div class="text-muted">Packets/s</div>
                            <strong id="pps">--</strong>
                          </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                          <div class="p-3 bg-light rounded metric-box live-metric">
                            <div class="text-muted">Alerts/s</div>
                            <strong id="als">--</strong>
                          </div>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-12">
                          <div class="card">
                            <div class="card-body">
                                  <div id="flows-chart" style="height:250px; width:100%;"></div>
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
        </div>
      </div>
    </div>

    <script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

    <script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>

    <script src="{{ asset('admin/assets/js/main.js') }}"></script>

    <script>
      // simple client-side demo: update timestamps every 5s
      setInterval(()=>{
        document.getElementById('last-update').textContent = new Date().toLocaleString();
      },5000);
    </script>

    <script src="{{ asset('admin/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
      // Live chart demo (simulated data)
      (function(){
        const chartEl = document.querySelector('#flows-chart');
        if(!chartEl) return;

        function generateInitial(count){
          const data = [];
          let t = Date.now() - (count-1)*2000;
          let v = Math.round(50 + Math.random()*20);
          for(let i=0;i<count;i++){
            data.push([t, v]);
            t += 2000;
            v = Math.max(0, Math.round(v + (Math.random()*20 - 10)));
          }
          return data;
        }

        let seriesData = generateInitial(20);

        const options = {
          chart: { type: 'area', height: 250, animations: { enabled: true, easing: 'linear', dynamicAnimation: { speed: 800 } }, toolbar: { show: false } },
          series: [{ name: 'Flows', data: seriesData }],
          stroke: { curve: 'smooth' },
          xaxis: { type: 'datetime' },
          tooltip: { x: { format: 'HH:mm:ss' } },
          colors: ['#0d6efd']
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();

        function pushPoint(){
          const now = Date.now();
          const last = seriesData.length ? seriesData[seriesData.length-1][1] : 50;
          const next = Math.max(0, Math.round(last + (Math.random()*20 - 10)));
          seriesData.push([now, next]);
          if(seriesData.length > 40) seriesData.shift();
          chart.updateSeries([{ data: seriesData }], false);

          // update small metric boxes
          const flowsEl = document.getElementById('flows');
          const ppsEl = document.getElementById('pps');
          const alsEl = document.getElementById('als');
          if(flowsEl) flowsEl.textContent = next;
          if(ppsEl) ppsEl.textContent = Math.round(next * (0.8 + Math.random()*0.6));
          if(alsEl) alsEl.textContent = Math.round(Math.max(0, Math.random()*2));
          // update timestamp
          const lastUpdate = document.getElementById('last-update');
          if(lastUpdate) lastUpdate.textContent = new Date().toLocaleString();
        }

        // start updates every 2s
        setInterval(pushPoint, 2000);
      })();
    </script>
  </body>
</html>
