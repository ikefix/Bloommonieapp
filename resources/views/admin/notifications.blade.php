@extends('layouts.adminapp')

@section('admincontent')

<style>
.notification-list{
    list-style:none;
    padding:0;
}

.notification-item{
    margin-bottom:10px;
    padding:15px;
    border-radius:10px;
}

.unread-notification{
    background:#e7f3ff;
    border-left:5px solid #1877f2;
}

.read-notification{
    background:#1f1f1f;
    color:#fff;
    border-left:5px solid #555;
}

.notification-time{
    color:#999;
    font-size:12px;
}

.action-btns{
    margin-top:10px;
}

.mark-read-btn{
    display:inline-block;
    padding:5px 10px;
    background:#28a745;
    color:white;
    text-decoration:none;
    border-radius:5px;
    margin-right:5px;
}

.delete-btn{
    border:none;
    background:#dc3545;
    color:white;
    padding:5px 10px;
    border-radius:5px;
}
</style>

<h3>Unread Notifications</h3>

@if($unreadNotifications->count())

<ul class="notification-list">

@foreach($unreadNotifications as $notification)

<li class="notification-item unread-notification">

    <strong>{{ $notification->data['message'] ?? 'Notification' }}</strong>

    <br>

    {{-- LOW STOCK --}}
    @if(($notification->data['type'] ?? '') == 'low_stock')

        <small>Product:
            <b>{{ $notification->data['product_name'] ?? 'N/A' }}</b>
        </small>

        <br>

        <small>Remaining:
            <b>{{ $notification->data['stock_quantity'] ?? 0 }}</b>
        </small>

    {{-- INVOICE --}}
    @elseif(($notification->data['type'] ?? '') == 'invoice_payment')

        <small>Invoice:
            <b>{{ $notification->data['invoice_no'] ?? 'N/A' }}</b>
        </small>

        <br>

        <small>Updated By:
            <b>{{ $notification->data['updated_by'] ?? 'N/A' }}</b>
        </small>

        <br>

        <small>Added:
            <b>₦{{ number_format($notification->data['amount_added'] ?? 0, 2) }}</b>
        </small>

        <br>

        <small>Balance:
            <b>₦{{ number_format($notification->data['balance'] ?? 0, 2) }}</b>
        </small>

    @endif

    <br><br>

    <span class="notification-time">
        {{ $notification->created_at->format('d M Y h:i A') }}
    </span>

    <div class="action-btns">
        <a href="{{ route('notifications.markAsRead', $notification->id) }}"
           class="mark-read-btn">Mark as Read</a>

        <form action="{{ route('notifications.delete', $notification->id) }}"
              method="POST"
              style="display:inline;">
            @csrf
            @method('DELETE')

            <button class="delete-btn">Delete</button>
        </form>
    </div>

</li>

@endforeach

</ul>

@else
<p>No unread notifications</p>
@endif


<hr>


<h3>Read Notifications</h3>

@if($readNotifications->count())

<ul class="notification-list">

@foreach($readNotifications as $notification)

<li class="notification-item read-notification">

    <strong>{{ $notification->data['message'] ?? 'Notification' }}</strong>

    <br>

    @if(($notification->data['type'] ?? '') == 'low_stock')

        <small>Product:
            <b>{{ $notification->data['product_name'] ?? 'N/A' }}</b>
        </small>

        <br>

        <small>Remaining:
            <b>{{ $notification->data['stock_quantity'] ?? 0 }}</b>
        </small>

    @elseif(($notification->data['type'] ?? '') == 'invoice_payment')

        <small>Invoice:
            <b>{{ $notification->data['invoice_no'] ?? 'N/A' }}</b>
        </small>

        <br>

        <small>Updated By:
            <b>{{ $notification->data['updated_by'] ?? 'N/A' }}</b>
        </small>

        <br>

        <small>Added:
            <b>₦{{ number_format($notification->data['amount_added'] ?? 0, 2) }}</b>
        </small>

        <br>

        <small>Balance:
            <b>₦{{ number_format($notification->data['balance'] ?? 0, 2) }}</b>
        </small>

    @endif

    <br><br>

    <span class="notification-time">
        {{ $notification->created_at->format('d M Y h:i A') }}
    </span>

    <form action="{{ route('notifications.delete', $notification->id) }}"
          method="POST">
        @csrf
        @method('DELETE')

        <button class="delete-btn">Delete</button>
    </form>

</li>

@endforeach

</ul>

@else
<p>No read notifications yet</p>
@endif

@endsection