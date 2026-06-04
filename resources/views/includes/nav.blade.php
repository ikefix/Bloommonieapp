<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('BloomMonie Pos', 'BloomMonie Pos') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">

            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Authentication Links -->
                @guest
                    @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                    @endif

                    @if (Route::has('register'))
                        <li class="nav-item">
                            {{-- <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a> --}}
                        </li>
                    @endif
                @else
                    <li class="nav-item dropdown admin-dash">
                        <a href="{{url("/home")}}" class="nav-link">Home</a>
                        <a href="{{ url("/cashiersales") }}" class="nav-link">Sales</a>
                        <a href="{{ route('cashierexpense.index') }}" class="nav-link">View Expenses</a>
                        <a href="{{ route('cashierexpense.create') }}" class="nav-link">Create Expense</a>
                        <a href="{{ route('cashier.invoices.create') }}" class="nav-link">Create Invoice</a>
                        <a href="{{ route('cashier.invoices.owing') }}" class="nav-link">Credit Supply</a>
                        <a href="{{ route('cashier.customers.index') }}" class="nav-link">Create Customer</a>
                        <a href="{{ route('cashier.invoices.receivables') }}" class="nav-link">Receivables</a>
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ Auth::user()->name }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                {{ __('Logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>