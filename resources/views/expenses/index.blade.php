@extends('layouts.adminapp')

@section('admincontent')
<div class="container">
    <h3><i class='bx bx-dollar' ></i> Expenses List</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h3></h3>
        <div>
            <button id="downloadPDF" class="btn btn-success btn-sm">📥 Download PDF</button>
            <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Print</button>
        </div>
    </div>


    <a href="{{ route('expenses.create') }}" class="btn btn-primary mb-3 no-print">+ Add Expense</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered" id="expensesTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Added By</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $expense->title }}</td>
                    <td>₦{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ \Carbon\Carbon::parse($expense->date)->format('M d, Y') }}</td>
                    <td>{{ $expense->user->name ?? 'Unknown User' }}</td>
                    <td>{{ $expense->description ?? '—' }}</td>
                    <td>
                        <form action="{{ route('cashierexpense.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Delete this expense?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No expenses found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $expenses->links() }}
</div>

<!-- ✅ PDF Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<!-- ✅ PDF Generation Script -->
<script>
document.getElementById("downloadPDF").addEventListener("click", function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Add header
    doc.setFontSize(16);
    doc.text("Cashier Sales Report", 14, 15);

    // Cashier info
    doc.setFontSize(11);
    doc.text("Cashier: {{ Auth::user()->name }}", 14, 23);
    doc.text("Date: {{ now()->format('F j, Y') }}", 14, 30);

    // Get table rows
    const table = document.getElementById("expensesTable");
    const rows = [];
    const headers = [];
    
    table.querySelectorAll("thead th").forEach(th => headers.push(th.innerText));
    table.querySelectorAll("tbody tr").forEach(tr => {
        const row = [];
        tr.querySelectorAll("td").forEach(td => {
            // Clean currency and remove special chars
            let text = td.innerText.replace(/₦/g, "N"); // Replace ₦ with N for PDF
            row.push(text);
        });
        rows.push(row);
    });

    // Add table to PDF
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 40,
        styles: {
            fontSize: 10,
            cellPadding: 2,
        },
        headStyles: { fillColor: [40, 40, 40] },
        alternateRowStyles: { fillColor: [245, 245, 245] },
    });

    // Save the file
    doc.save("cashier_sales_{{ Auth::user()->name }}.pdf");
});
</script>


<!-- ✅ Print Styling -->
<style>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: #fff;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th, td {
        border: 1px solid #000;
        padding: 6px;
        text-align: left;
    }

    h4 {
        margin-bottom: 5px;
    }
}
</style>
@endsection
