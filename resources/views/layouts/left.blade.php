<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
      <div class="sidebar-header border-bottom">
        <div class="sidebar-brand">
          <svg class="sidebar-brand-full" width="88" height="32" alt="CoreUI Logo">
            <use xlink:href="{{ asset('coreui/assets/brand/coreui.svg#full') }}"></use>
          </svg>
          <svg class="sidebar-brand-narrow" width="32" height="32" alt="CoreUI Logo">
            <use xlink:href="{{ asset('coreui/assets/brand/coreui.svg#signet') }}"></use>
          </svg>
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close" onclick="coreui.Sidebar.getInstance(document.querySelector(&quot;#sidebar&quot;)).toggle()"></button>
      </div>
      <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-speedometer') }}"></use></svg>
                {{ __('Dashboard') }}
            </a>   
        </li> 
        <li class="nav-item">
            <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Sector') }}
            </a>   
        </li> 
        
        <li class="nav-item">
            <a href="{{ route('subcategories.index') }}" class="nav-link {{ request()->routeIs('subcategories.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Sub Sector') }}
            </a>   
        </li>
        <li class="nav-group" aria-expanded="false">
        <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg> {{ __('Lead Buyers') }}</a>
              <ul class="nav-group-items compact" style="height: 100px;">
               
                <li class="nav-item">
                  <a href="{{ route('seller.incomplete') }}" class="nav-link {{ request()->routeIs('seller.incomplete') ? 'active' : '' }}">
                    <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                      {{ __('Incomplete') }}
                  </a>   
                </li>
                <li class="nav-item">
                  <a href="{{ route('seller.complete') }}" class="nav-link {{ request()->routeIs('seller.complete') ? 'active' : '' }}">
                    <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                      {{ __('Complete') }}
                  </a>   
                </li>
              </ul>
        </li>
        <li class="nav-item">
            <a href="{{ route('buyer.index') }}" class="nav-link {{ request()->routeIs('buyer.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Quote Customers') }}
            </a>   
        </li>
        <li class="nav-item">
            <a href="{{ route('servicequestion.index') }}" class="nav-link {{ request()->routeIs('servicequestion.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Service Questions') }}
            </a>   
        </li>
        <li class="nav-item">
            <a href="{{ route('profilequestion.index') }}" class="nav-link {{ request()->routeIs('profilequestion.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Profile Questions') }}
            </a>   
        </li>
        <li class="nav-item">
            <a href="{{ route('blogs.index') }}" class="nav-link {{ request()->routeIs('blogs.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Blogs') }}
            </a>   
        </li>

        <li class="nav-item">
            <a href="{{ route('plans.index') }}" class="nav-link {{ request()->routeIs('plans.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Plans') }}
            </a>   
        </li>
        <li class="nav-item">
            <a href="{{ route('coupon.index') }}" class="nav-link {{ request()->routeIs('coupon.index') ? 'active' : '' }}">
                <svg class="nav-icon"><use xlink:href="{{ asset('coreui/node_modules/@coreui/icons/sprites/free.svg#cil-puzzle') }}"></use></svg>
                {{ __('Coupons') }}
            </a>   
        </li>
      
      </ul>

</div>