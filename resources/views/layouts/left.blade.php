<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
  <div class="sidebar-header border-bottom">
    <div class="sidebar-brand">
      <img src="{{asset('/images/localist_logo.svg')}}" height="100" width="100" class="mt-2" />
      <!-- <svg class="sidebar-brand-full" width="88" height="32" alt="CoreUI Logo">
            <use xlink:href="{{ asset('images/assets/brand/coreui.svg#full') }}"></use>
          </svg>
          <svg class="sidebar-brand-narrow" width="32" height="32" alt="CoreUI Logo">
            <use xlink:href="{{ asset('coreui/assets/brand/coreui.svg#signet') }}"></use>
          </svg> -->
    </div>
    <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close" onclick="coreui.Sidebar.getInstance(document.querySelector(&quot;#sidebar&quot;)).toggle()"></button>
  </div>
  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
    <li class="nav-item">
      <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-speedometer') }}"></use>
        </svg>
        {{ __('Dashboard') }}
      </a>
    </li>

    @can('sector.viewlist')
    <li class="nav-item">
      <a href="{{ route('sectors.index') }}" class="nav-link {{ request()->routeIs('sectors.index') ? 'active' : '' }}">
        <i class="bi bi-list nav-icon"></i>
        Sectors
      </a>
    </li>
    @endcan

    @can('adminuser.view')
    <li class="nav-item">
      <a href="{{ route('admin-users.index') }}" class="nav-link {{ request()->routeIs('admin-users.index') ? 'active' : '' }}">
        <i class="bi bi-list nav-icon"></i>
        Manage Admin Users
      </a>
    </li>
    @endcan

    @can('role.view')
    <li class="nav-item">
      <a href="{{ url('roles') }}" class="nav-link {{ Route::currentRouteName() == 'roles.index' ? 'active' : '' }}">
        <i class="nav-icon fas fa-user-shield"></i>
        Roles
      </a>
    </li>
    @endcan

    {{-- <li class="nav-item">
            <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.index') ? 'active' : '' }}">
    <i class="bi bi-list nav-icon"></i>
    {{ __('Sector') }}
    </a>
    </li>

    <li class="nav-item">
      <a href="{{ route('subcategories.index') }}" class="nav-link {{ request()->routeIs('subcategories.index') ? 'active' : '' }}">
        <i class="bi bi-list nav-icon"></i>
        {{ __('Sub Sector') }}
      </a>
    </li> --}}

    @canany(['leadbuyers.incomplete-viewlist','leadbuyers.viewlist','leadbuyerscontact.viewlist'])

    <li class="nav-group" aria-expanded="false">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="fa-solid fa-user nav-icon"></i> {{ __('Lead Buyers') }}</a>
      <ul class="nav-group-items compact" style="height: 170px;">

        @can('leadbuyers.incomplete-viewlist')
        <li class="nav-item">
          <a href="{{ route('seller.incomplete') }}" class="nav-link {{ request()->routeIs('seller.incomplete') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Incomplete') }}
          </a>
        </li>
        @endcan
        @can('leadbuyers.viewlist')
        <li class="nav-item">
          <a href="{{ route('seller.complete') }}" class="nav-link {{ request()->routeIs('seller.complete') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Complete') }}
          </a>
        </li>
        @endcan


         @can('leadbuyers.allloginhistorylist')
        <li class="nav-item">
          <a href="{{ route('seller.allloginhistorylist') }}" class="nav-link {{ request()->routeIs('seller.allloginhistorylist') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Login History') }}
          </a>
        </li>
         @endcan

        @can('leadbuyerscontact.viewlist')
        <li class="nav-item">
          <a href="{{ route('seller.contact_form') }}" class="nav-link {{ request()->routeIs('seller.contact_form') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Contact Form') }}
          </a>
        </li>
        @endcan



      </ul>
    </li>

    @endcanany

    @canany(['quotecustomers.incom-viewlist','quotecustomers.complete-viewlist','quotecustomers.conatct-viewlist' ,'quotecustomers.test-complete-list','quotecustomers.quote_test_incomplete_list'])
    <li class="nav-group" aria-expanded="false">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="fa-solid fa-users nav-icon"></i> {{ __('Quote Customers') }}</a>
      <ul class="nav-group-items compact" style="height: 210px;">

        @can('quotecustomers.incom-viewlist')
        <li class="nav-item">
          <a href="{{ route('buyer.incompletelist') }}" class="nav-link {{ request()->routeIs('buyer.incompletelist') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Incomplete') }}
          </a>
        </li>
        @endcan
        @can('quotecustomers.complete-viewlist')
        <li class="nav-item">
          <a href="{{ route('buyer.index') }}" class="nav-link {{ request()->routeIs('buyer.index') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Complete') }}
          </a>
        </li>

        @endcan
        @can('quotecustomers.quote_test_incomplete_list')

        <li class="nav-item">
          <a href="{{ route('buyer.testuserincompletelist') }}" class="nav-link {{ request()->routeIs('buyer.testuserincompletelist') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Test Incomplete') }}
          </a>
        </li>
        @endcan
        @can('quotecustomers.test-complete-list')
        <li class="nav-item">
          <a href="{{ route('buyer.testusercompletelist') }}" class="nav-link {{ request()->routeIs('buyer.testusercompletelist') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Test Complete') }}
          </a>
        </li>
        @endcan
        @can('quotecustomers.conatct-viewlist')
        <li class="nav-item">
          <a href="{{ route('buyer.contact_form') }}" class="nav-link {{ request()->routeIs('buyer.contact_form') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            {{ __('Contact Form') }}
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcanany
    <!-- <li class="nav-item">
            <a href="{{ route('buyer.index') }}" class="nav-link {{ request()->routeIs('buyer.index') ? 'active' : '' }}">
            <i class="fa-solid fa-users nav-icon"></i>
                {{ __('Quote Customers') }}
            </a>
        </li> -->
    @can('servicequestions.viewlist')
    <li class="nav-item">
      <a href="{{ route('servicequestion.index') }}" class="nav-link {{ request()->routeIs('servicequestion.index') ? 'active' : '' }}">
        <i class="bi bi-question-circle nav-icon"></i> {{ __('Service Questions') }}
      </a>
    </li>
    @endcan

    @can('profilequestions.viewlist')
    <li class="nav-item">
      <a href="{{ route('profilequestion.index') }}" class="nav-link {{ request()->routeIs('profilequestion.index') ? 'active' : '' }}">
        <i class="bi bi-question-octagon nav-icon"></i>
        {{ __('Profile Questions') }}
      </a>
    </li>
    @endcan
    @can('requestlist.viewlist')
    <li class="nav-item">
      <a href="{{ route('request-list.index') }}" class="nav-link {{ request()->routeIs('request-list.index') ? 'active' : '' }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use>
        </svg>
        {{ __('Request List (Leads)') }}
      </a>
    </li>
    @endcan

    @can('invoicehistory.viewlist')
    <li class="nav-item">
      <a href="{{ route('purchase.invoice.history') }}" class="nav-link {{ request()->routeIs('purchase.invoice.history') ? 'active' : '' }}">
        <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
        {{ __('Purchase Invoice History') }}
      </a>
    </li>
    @endcan


    @can('leadbuyerpostcodes.viewlist')
    <li class="nav-item">
      <a href="{{ route('export.leadbuyer.service.postcodes') }}" class="nav-link {{ request()->routeIs('export.leadbuyer.service.postcodes') ? 'active' : '' }}">
        <i class="fa-solid fa-file-export nav-icon"></i>
        {{ __('Export Buyer PostCodes') }}
      </a>
    </li>
    @endcan


    @can('blog.viewlist')
    <li class="nav-item">
      <a href="{{ route('blogs.index') }}" class="nav-link {{ request()->routeIs('blogs.index') ? 'active' : '' }}">
        <i class="fa-solid fa-blog nav-icon"></i>
        {{ __('Blogs') }}
      </a>
    </li>
    @endcan

    @can('page.viewlist')
    <li class="nav-item">
      <a href="{{ route('pages.index') }}" class="nav-link {{ request()->routeIs('pages.index') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-fill nav-icon"></i>
        {{ __('Pages') }}
      </a>
    </li>
    @endcan
    @can('footermenus.viewlist')
    <li class="nav-item">
      <a href="{{ route('menus.index') }}" class="nav-link {{ request()->routeIs('menus.index') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-fill nav-icon"></i>
        {{ __('Footer Menus') }}
      </a>
    </li>
    @endcan

    @can('plans.viewlist')
    <li class="nav-item">
      <a href="{{ route('plans.index') }}" class="nav-link {{ request()->routeIs('plans.index') ? 'active' : '' }}">
        <i class="bi bi-list-task nav-icon"></i>
        {{ __('Plans') }}
      </a>
    </li>
    @endcan
    @can('coupons.viewlist')
    <li class="nav-item">
      <a href="{{ route('coupon.index') }}" class="nav-link {{ request()->routeIs('coupon.index') ? 'active' : '' }}">
        <i class="fa-solid fa-gift nav-icon"></i>
        {{ __('Coupons') }}
      </a>
    </li>
    @endcan


    @canany(['generalsettings.viewlist', 'email-settings.viewlist'])
    <li class="nav-group" aria-expanded="false">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="bi bi-gear nav-icon"></i> Settings
      </a>
      <ul class="nav-group-items compact" style="height: 100px;">

        @can('generalsettings.viewlist')
        <li class="nav-item">
          <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            General Settings
          </a>
        </li>
        @endcan

        @can('email-settings.viewlist')
        <li class="nav-item">
          <a href="{{ route('email-settings.index') }}" class="nav-link {{ request()->routeIs('email-settings.index') ? 'active' : '' }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Email Settings
          </a>
        </li>
        @endcan

      </ul>
    </li>
    @endcanany

    <li class="nav-item">
      <a href="{{ route('service-map.index') }}" class="nav-link {{ request()->routeIs('service-map.index') ? 'active' : '' }}">
        <i class="bi bi-geo-alt nav-icon"></i>
        Service Map
      </a>
    </li>


    @can('d7leadsuppliers.viewlist')
    <li class="nav-item">
      <a href="{{ route('d7LeadSupplierList') }}" class="nav-link {{ request()->routeIs('d7LeadSupplierList') ? 'active' : '' }}">
        <i class="fa-solid fa-user nav-icon"></i>
        D7 Lead Suppliers
      </a>
    </li>
    @endcan

    @can('import-marketing-contacts')
    <li class="nav-item">
      <a href="{{ route('zoho.viewimport') }}" class="nav-link {{ request()->routeIs('zoho.viewimport') ? 'active' : '' }}">
        <i class="fa-solid fa-user nav-icon"></i>
        Import Marketing Contact
      </a>
    </li>
    @endcan
  </ul>

</div>