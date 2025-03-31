<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }} </x-slot>

  <div class="container">
      <div class="row g-4 mb-4">
                <div class="col-sm-6 col-md-3">
                  
                    <div class="card text-white bg-primary">
                      <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fs-4 fw-semibold">{{$totalusers}}</div>
                          <a href="{{route('user.index','users')}}" class="text text-white"><div>Total Users</div> </a>
                        </div>
                        <div class="dropdown">
                          <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <svg class="icon">
                              <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                            </svg>
                          </button>
                          <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
                        </div>
                      </div>
                      <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                      </div>
                    </div>
                 
                </div>
                <!-- /.col-->
                <div class="col-sm-6 col-md-3">
                  <div class="card text-white bg-info">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fs-4 fw-semibold">{{$totalsellers}} </div>
                        <a href="{{route('user.index','sellers')}}" class="text text-white"><div>No. of Sellers</div></a>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <svg class="icon">
                            <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                          </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
                      </div>
                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                      <canvas class="chart" id="card-chart2" height="70"></canvas>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
                <div class="col-sm-6 col-md-3">
                  <div class="card text-white bg-warning">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fs-4 fw-semibold">{{$totalbuyer}} </div>
                        <a href="{{route('user.index','buyers')}}" class="text text-white"><div>No. of Buyers</div></a>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <svg class="icon">
                            <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                          </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
                      </div>
                    </div>
                    <div class="c-chart-wrapper mt-3" style="height:70px;">
                      <canvas class="chart" id="card-chart3" height="70"></canvas>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
                <div class="col-sm-6 col-md-3">
                  <div class="card text-white bg-danger">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fs-4 fw-semibold">{{$inactiveusers}} </div>
                        <a href="{{route('user.index','inactive')}}" class="text text-white"><div>Inactive Users</div></a>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <svg class="icon">
                            <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                          </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                          <a class="dropdown-item" href="#">Action</a>
                          <a class="dropdown-item" href="#">Another action</a>
                          <a class="dropdown-item" href="#">Something else here</a>
                        </div>
                      </div>
                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                      <canvas class="chart" id="card-chart4" height="70"></canvas>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
      </div>
      <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                  <div class="card text-white bg-success">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fs-4 fw-semibold">{{$activeusers}} </div>
                        <a href="{{route('user.index','active')}}" class="text text-white"><div>Active Users</div></a>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <svg class="icon">
                            <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                          </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
                      </div>
                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                      <canvas class="chart" id="card-chart4" height="70"></canvas>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
                <div class="col-sm-6 col-md-3">
                  <div class="card text-white bg-secondary">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fs-4 fw-semibold">{{$totalcategories}} </div>
                        <a href="{{route('categories.index')}}" class="text text-white"><div>Total Categories</div></a>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <svg class="icon">
                            <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                          </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
                      </div>
                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                      <canvas class="chart" id="card-chart2" height="70"></canvas>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
                <div class="col-sm-6 col-md-3">
                  <div class="card text-white bg-dark">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fs-4 fw-semibold">{{$totalblogs}} </div>
                        <a href="{{route('blogs.index')}}" class="text text-white"><div>Total Blogs</div></a>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <svg class="icon">
                            <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                          </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
                      </div>
                    </div>
                    <div class="c-chart-wrapper mt-3" style="height:70px;">
                      <canvas class="chart" id="card-chart3" height="70"></canvas>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-md-3">
                  <div class="card text-white bg-dark">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fs-4 fw-semibold">{{$totalblogs}} </div>
                        <div>Total Orders</div>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <svg class="icon">
                            <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-options"></use>
                          </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
                      </div>
                    </div>
                    <div class="c-chart-wrapper mt-3" style="height:70px;">
                      <canvas class="chart" id="card-chart3" height="70"></canvas>
                    </div>
                  </div>
                </div>
                
      </div>
  </div>

 
</x-app-layout>           