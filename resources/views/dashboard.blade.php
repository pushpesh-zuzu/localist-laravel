<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }} </x-slot>

    <div class="container">

        <div class="col-md-12">

        <div class="row g-4 mb-4">
            <h4 class="card-title font-weight-bold d-block w-100 mb-3"> Seller Leads</h4>
            <div class="col-sm-6 col-md-3">

                <div class="card text-white bg-primary">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $sellerTotalHired }}</div>
                            <a href="#" class="text text-white">
                                <div> Leads Hired</div>
                            </a>
                        </div>

                        <div>
                            <div class="fs-4 fw-semibold">{{ $sellerTotalBid }}</div>
                            <a href="#" class="text text-white">
                                <div> Total Bid</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>

            </div>
        </div>
        <div class="row g-4 mb-4">
            <h4 class="card-title font-weight-bold d-block w-100 mb-3"> Active Sellers</h4>
            <div class="col-sm-6 col-md-3">

                <div class="card text-white  bg-info">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $dailyActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Daily Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>
            <div class="col-sm-6 col-md-3">

                <div class="card text-white bg-warning">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $monthlyActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Monthly Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>
            <div class="col-sm-6 col-md-3">

                <div class="card text-white bg-danger">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $quarterlyActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Quarterly Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>

            <div class="col-sm-6 col-md-3">

                <div class="card text-white bg-success">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $yearlyActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Yearly Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>


            <!-- /.col-->

            <!-- /.col-->

            <!-- /.col-->

            <!-- /.col-->
        </div>
        <div class="row g-4 mb-4">
            <h4 class="card-title font-weight-bold d-block w-100 mb-3">In-Active  Seller </h4>
            <div class="col-sm-6 col-md-3">

                <div class="card text-white  bg-info">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $dailyInActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Daily In-Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>
            <div class="col-sm-6 col-md-3">

                <div class="card text-white bg-warning">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $monthlyInActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Monthly In-Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>
            <div class="col-sm-6 col-md-3">

                <div class="card text-white bg-danger">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $quarterlyInActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Quarterly In-Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>

            <div class="col-sm-6 col-md-3">

                <div class="card text-white bg-success">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $yearlyInActiveSellers }}</div>
                            <a href="#" class="text text-white">
                                <div>Yearly In-Active Sellers</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>



            </div>


            <!-- /.col-->

            <!-- /.col-->

            <!-- /.col-->

            <!-- /.col-->
        </div>
        <div class="row g-4 mb-4">
            <h4 class="card-title font-weight-bold d-block w-100 mb-3"> Seller Sectors</h4>
            @if(count($categoryUserCounts) > 0)
            @foreach($categoryUserCounts as $value)
            <div class="col-sm-6 col-md-3">

                <div class="card text-white  bg-info">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-start">

                        <div>
                            <div class="fs-4 fw-semibold">{{ $value['user_count'] }}</div>
                            <a href="#" class="text text-white">
                                <div>{{ $value['category_name'] }}</div>
                            </a>
                        </div>

                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>

            </div>
            @endforeach
            @endif

        </div>
        </div>

    </div>


</x-app-layout>
