<x-app-layout>
    <x-slot name="header">{{ __('Request List') }} </x-slot>

    <div class="card mb-4">
      <div class="card-header">
          <strong>{{ __('Request List') }}</strong>
      </div>
      <div class="card-body">
       <div class="row">
        <div class="col-md-12">
          <table id="cat-table" class="table table-bordered table-striped">
            <thead>
              <th width="20px;">#</th>
              <th>Customer</th>
              <th>Loacation</th>
              <th>DateTime</th>
              <th>Category</th>              
              <th>Credits</th>             
              <th>Urgent</th>
              <th>High</th>
              <th>Verified</th>              
              <th>Additional</th>
              <th>Frequent</th>
              <th>Status</th>
              <th>Que/Ans</th>
              <th>Additional Dtails</th>
            </thead>
            <tbody>            
             
            </tbody>
          </table>
        </div>
       </div>
      </div>
    </div>

    @push('scripts')
    <script>
      $(document).ready(function(){
          var url = $("#_url").val();
          $("#cat-table").DataTable({
              dom: 'Bfrtip',
              responsive: true,
              processing: true,
              serverSide: true,
              autoWidth:false,
              buttons: [
                  // 'csv',
                  'pageLength'           
              ],
              exportOptions: {
                  modifier: {
                      search: 'applied',
                      order: 'applied',
                      page: 'all' // This ensures all pages are exported
                  }
              },
             ajax: url + "/request-list",
              
              columns: [
                  { data: 'DT_RowIndex', name: 'DT_RowIndex',orderable: false,  searchable: false},
                  { data: 'customer_name', name: 'customer_name' },
                  { data: 'city', name: 'city' },
                  { data: 'created_at', name: 'created_at' },
                  { data: 'category_name', name: 'category_name' },
                  { data: 'credit_score', name: 'credit_score' },
                  { data: 'is_urgent', name: 'is_urgent' },
                  { data: 'is_high_hiring', name: 'is_high_hiring' },
                  { data: 'is_phone_verified', name: 'is_phone_verified' },
                  { data: 'has_additional_details', name: 'has_additional_details' },
                  { data: 'is_frequent_user', name: 'is_frequent_user' },
                  { data: 'status', name: 'status' },
                  { data: 'questions', name: 'questions' },
                  { data: 'details', name: 'details' },
                  
              ],
              order: [[2, 'desc']] // Optional: default order
          }); 
  
      });
      
  </script>
    @endpush

</x-app-layout>           

