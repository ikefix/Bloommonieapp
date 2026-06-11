
@if(Auth::user()->role === 'admin')

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
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bxs-dashboard bx-tada' ></i></span>
                <span class="sidebar-text">Dashboard</span>
            </a>
            <a class="sidebar-link collapsible-btn" onclick="toggleSubmenu(this)">
                <span class="sidebar-icon"><i class='bx bx-layer'></i></span>
                <span class="sidebar-text">Inventory</span>
                <span class="arrow">&#9662;</span> <!-- down arrow -->
            </a>
            <div class="submenu"> 
                 <a href="{{ route('products.create') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-package'></i></span>
                    <span class="sidebar-text">Products</span>
                </a>
                <a href="{{ route('categories.create') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-category'></i></span>
                    <span class="sidebar-text">Categories</span>
                </a>
            </div>
            <a href="{{route('admin.sales')}}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bx-receipt'></i></span>
                <span class="sidebar-text">Sales</span>
            </a>

            <a class="sidebar-link collapsible-btn" onclick="toggleSubmenu(this)">
                <span class="sidebar-icon"><i class='bx bx-credit-card'></i></span>
                <span class="sidebar-text">Expenses</span>
                <span class="arrow">&#9662;</span> <!-- down arrow -->
            </a>
            <div class="submenu"> 
                <a href="{{ route('expenses.create') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-purchase-tag'></i></span>
                    <span class="sidebar-text">Create Expense</span>
                </a>
                <a href="{{ route('expenses.index') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bxs-discount'></i></span>
                    <span class="sidebar-text">View Expenses</span>
                </a>
            </div>
            <a href="{{route('shops.create')}}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bxs-store' ></i></span>
                <span class="sidebar-text">Shops</span>
            </a>
            <a class="sidebar-link collapsible-btn" onclick="toggleSubmenu(this)">
                <span class="sidebar-icon"><i class='bx bx-layer'></i></span>
                <span class="sidebar-text">Report</span>
                <span class="arrow">&#9662;</span> <!-- down arrow -->
            </a>
            <div class="submenu"> 
                 
                 <a href="{{route('admin.report.sales_report')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-package'></i></span>
                    <span class="sidebar-text">Sales Report</span>
                </a>
                <a href="{{route('admin.report.stock_report')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-category'></i></span>
                    <span class="sidebar-text">Stock Report</span>
                </a>
                <a href="{{route('admin.report.profit_loss')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-category'></i></span>
                    <span class="sidebar-text">P & L Report</span>
                </a>
                <a href="{{route('admin.report.production_report')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-category'></i></span>
                    <span class="sidebar-text">Prod & Manu Report</span>
                </a>
            </div>
            <a href="{{route('stock-transfers.create')}}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bx-transfer'></i></span>
                <span class="sidebar-text">Stock Transfer</span>
            </a>   

            <a class="sidebar-link collapsible-btn" onclick="toggleSubmenu(this)">
                <span class="sidebar-icon"><i class='bx bx-group'></i></span>
                <span class="sidebar-text">Customers</span>
                <span class="arrow">&#9662;</span> <!-- down arrow -->
            </a>
            <div class="submenu"> 
                <a href="{{route('admin.invoices.owing')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-credit-card-front'></i></span>
                    <span class="sidebar-text">Credit Supply</span>
                </a>  
                <a href="{{ route('admin.customers.index') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bxs-discount'></i></span>
                    <span class="sidebar-text">Create Customer</span>
                </a>  
                <a href="{{ route('admin.invoices.create') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-file'></i></span>
                    <span class="sidebar-text">Create Invoice</span>
                </a>  
                <a href="{{ route('admin.invoices.receivables') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-search'></i></span>
                    <span class="sidebar-text">Receivables</span>
                </a>  
                <a href="{{ route('admin.collectables') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-collection'></i></span>
                    <span class="sidebar-text">Collectables</span>
                </a>  
            </div>

             <a class="sidebar-link collapsible-btn" onclick="toggleSubmenu(this)">
                <span class="sidebar-icon"><i class='bx bxs-factory'></i></span>
                <span class="sidebar-text">Prod & Manu</span>
                <span class="arrow">&#9662;</span> <!-- down arrow -->
            </a>
            <div class="submenu"> 
                <a href="{{route('admin.production_type.index')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-credit-card-front'></i></span>
                    <span class="sidebar-text">Production Type</span>
                </a>
                <a href="{{route('admin.production.index')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bxs-layer'></i></span>
                    <span class="sidebar-text">Batch</span>
                </a>    
                <a href="{{route('admin.production_entries.index')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-clipboard'></i></span>
                    <span class="sidebar-text">List </span>
                </a>
            </div>

            <a class="sidebar-link collapsible-btn" onclick="toggleSubmenu(this)">
                <span class="sidebar-icon"><i class='bx bx-cog'></i></span>
                <span class="sidebar-text">Settings</span>
                <span class="arrow">&#9662;</span> <!-- down arrow -->
            </a>
            <div class="submenu">
                <a href="{{ route('barcode.manager') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-barcode-reader'></i></span>
                    <span class="sidebar-text">Barcode Manager</span>
                </a> 
                <a href="{{route('admin.manager-permissions')}}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-shield-quarter'></i></span>
                    <span class="sidebar-text">Permissions</span>
                </a>  
            </div>

            {{-- <a href="{{route('reports.sales')}}" class="sidebar-link">
                <span class="sidebar-icon">🏢</span>
                <span class="sidebar-text">Report</span>
            </a>      --}}
            <a href="{{ route('user.notifications') }}" class="sidebar-link">
                <span class="sidebar-icon notify"><span class="badge bg-danger">{{ $unreadNotificationCount }}</span><i class='bx bx-bell'></i></span>
                <span class="sidebar-text">
                    Notification
                    @if(isset($unreadNotificationCount) && $unreadNotificationCount > 0)
                        {{-- <span class="badge bg-danger">{{ $unreadNotificationCount }}</span> --}}
                    @endif
                </span>
            </a>         
            <a class="sidebar-link collapsible-btn" onclick="toggleSubmenu(this)">
                <span class="sidebar-icon"><i class='bx bx-user'></i></span>
                <span class="sidebar-text">Users</span>
                <span class="arrow">&#9662;</span> <!-- down arrow -->
            </a>
            <div class="submenu">
                <a href="{{ route('admin.manage_roles') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-user-check'></i></span>
                    <span class="sidebar-text">Manage Users</span>
                </a>
                <a href="{{ route('admin.register') }}" class="sidebar-link">
                    <span class="sidebar-icon"><i class='bx bx-user-plus'></i></span>
                    <span class="sidebar-text">Add Staff</span>
                </a>
            </div>
            <a href="{{ route('complaints.create') }}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bx-user-circle'></i></span>
                <span class="sidebar-text">Customer Care</span>
            </a>
            <a href="{{ route('admin.profile') }}" class="sidebar-link">
                <span class="sidebar-icon"><i class='bx bx-user-circle'></i></span>
                <span class="sidebar-text">Profile</span>
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

