@extends('layouts.adminapp')

@section('admincontent')
    <div class="container">


        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="shop-flex-wrapper">
            <!-- Form for creating or editing the shop -->
            <div class="shop-form-section">
                <form action="{{ isset($editingShop) ? route('shops.update', $editingShop->id) : route('shops.store') }}" method="POST" id="shop-form">
                    <h2>{{ isset($editingShop) ? 'Edit Shop' : 'Create New Shop' }}</h2>
                    @csrf
                    @if(isset($editingShop))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="name" class="form-label">Shop Name</label>
                        <input type="text" name="name" id="shop-name" class="form-control" required value="{{ isset($editingShop) ? $editingShop->name : '' }}">
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location (optional)</label>
                        <input type="text" name="location" id="shop-location" class="form-control" value="{{ isset($editingShop) ? $editingShop->location : '' }}">
                    </div>

                    <button type="submit" class="btn btn-primary" id="submit-btn">{{ isset($editingShop) ? 'Update Shop' : 'Create Shop' }}</button>
                </form>
            </div>

            <!-- Table of Shops -->
            <div class="shop-table-section">
                @if($shops->count())
                    <h3 class="mt-4">Your Shops</h3>
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Shop Name</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shops as $shop)
                            <tr>
                                <td>{{ $shop->name }}</td>
                                <td>{{ $shop->location }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-shop" 
                                        data-id="{{ $shop->id }}" 
                                        data-name="{{ $shop->name }}" 
                                        data-location="{{ $shop->location }}">
                                        Edit
                                    </button>
                                    
                                    <form action="{{ route('shops.destroy', $shop->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this shop?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="mt-4">You haven't created any shops yet.</p>
                @endif
            </div>
        </div>
        </div>

    </div>

    <script>
        document.querySelectorAll('.edit-shop').forEach(button => {
            button.addEventListener('click', function () {
                const shopId = this.dataset.id;
                const name = this.dataset.name;
                const location = this.dataset.location;
    
                // Set form values
                document.getElementById('shop-name').value = name;
                document.getElementById('shop-location').value = location;
    
                // Set form action
                const form = document.getElementById('shop-form');
                form.action = `/shops/${shopId}`;
                
                // Spoof PUT method
                let methodField = form.querySelector('input[name="_method"]');
                if (!methodField) {
                    methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    form.appendChild(methodField);
                }
                methodField.value = 'PUT';
    
                // Change button text
                document.getElementById('submit-btn').innerText = 'Update Shop';
            });
        });
    </script>

@endsection
