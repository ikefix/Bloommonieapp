@extends('layouts.adminapp')

@section('admincontent')
<div class="container">
    <h2>Manage User Roles</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="tab-role">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Current Role</th>
                    <th>Change Role</th>
                    <th>Action</th>
                    <th>Store</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ucfirst($user->role) }}</td>
                        <td>
                            <form action="{{ route('admin.updateRole', $user->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="form-control">
                                    <option value="cashier" {{ $user->role == 'cashier' ? 'selected' : '' }}>Cashier</option>
                                    <option value="manager" {{ $user->role == 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm mt-2">Update</button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('admin.deleteUser', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            <form method="POST" action="{{ route('users.toggle-restrict', $user->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="btn btn-sm {{ $user->is_restricted ? 'btn-success' : 'btn-danger' }}">
                                    {{ $user->is_restricted ? 'Unrestrict' : 'Restrict' }}
                                </button>
                            </form>

                            @if($user->is_restricted)
                                <span class="badge bg-danger">Restricted</span>
                            @endif
                        </td>
                        <td>
                            <select name="shop_id" id="shop_id" class="form-control" required>
                                <option value="">{{ $user->shop->name ?? 'Not assigned' }}</option>
                                {{-- @foreach ($users as $user)
                                    <option value="">{{ $user->shop->name ?? 'Not assigned' }}</option>
                                @endforeach --}}
                            </select>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
