
@if(Auth::user()->role === 'superadmin')

    <style>
        .bx{
            font-size: 1.5rem;
        }
    </style>
    <div id="adminSidebar" class="sidebar-expanded">
        <div class="sidebar-header">
            <img id="sidebarLogo" src="{{ asset('logobloomp.png') }}" alt="BloomMonie Dashboard" width="30px" style="border-radius: 100px">
            <button id="toggleSidebarBtn">&#9776;</button>
        </div>
        <nav class="sidebar-navigation">
            <a href="{{ route('superadmin.dashboard') }}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bxs-dashboard bx-tada' ></i></span>
                <span class="sidebar-text">Dashboard</span>
            </a>

            <a href="{{ route('superadmin.create')}}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bxs-dashboard bx-tada' ></i></span>
                <span class="sidebar-text">Create Admin</span>
            </a>
            
            <a id="navbarDropdown" class="sidebar-link" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                <span class="sidebar-icon"><i class='bx bxs-user'></i></span> <!-- Profile Icon -->
                <span class="sidebar-text">{{ Auth::user()->name }}</span> <!-- User's name -->
            </a>
            
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <a class="dropdown-item logout-item" href="{{ route('logout') }}"
                   onclick="event.preventDefault();
                             document.getElementById('logout-form').submit();">
                    <span class="sidebar-icon"><i class='bx bx-door-open'></i></span> <!-- Logout icon -->
                    <span class="sidebar-text">{{ __('Logout') }}</span>
                </a>
            
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>            
        </nav>
    </div>
@endif

