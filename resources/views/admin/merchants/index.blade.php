@extends('admin.layout')

@section('title', 'Merchants')
@section('heading', 'Merchants')

@push('styles')
@if($totalMerchantsCount > 0)
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
@endif
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        gap: 16px;
    }

    .page-title {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.5px;
        color: #202223;
        margin: 0 0 4px 0;
    }

    .page-description {
        color: #6d7175;
        font-size: 13px;
        margin: 0;
    }

    .merchant-count {
        background: #e4f5ec;
        color: #008060;
        font-size: 11px;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* Table Card */
    .table-card {
        background: #ffffff;
        border: 1px solid #e1e3e5;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
    }

    .table-heading {
        padding: 16px 20px;
        font-size: 14px;
        font-weight: 700;
        border-bottom: 1px solid #e1e3e5;
        color: #202223;
    }

    .table-wrap {
        padding: 0 20px 16px;
        overflow-x: auto;
    }

    /* DataTables Overrides to match Socialvire design */
    table.dataTable {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 0 !important;
        font-family: 'Inter', sans-serif;
    }

    table.dataTable thead th {
        background: #f9fafb !important;
        color: #6d7175 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: .5px !important;
        padding: 14px 16px !important;
        border-bottom: 1px solid #e1e3e5 !important;
        border-top: none !important;
        white-space: nowrap;
    }

    table.dataTable tbody td {
        padding: 14px 16px !important;
        border-bottom: 1px solid #f1f2f3 !important;
        border-top: none !important;
        color: #202223;
        font-size: 12px;
        vertical-align: middle;
    }

    table.dataTable.no-footer {
        border-bottom: 1px solid #e1e3e5 !important;
    }

    .store-name {
        font-weight: 700;
        color: #202223;
        margin-bottom: 4px;
    }

    .store-domain {
        color: #8c9196;
        font-size: 11px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.2;
    }

    .status-active {
        background: #e4f5ec;
        color: #008060;
    }

    .status-uninstalled {
        background: #fcebea;
        color: #b42318;
    }

    .status-inactive {
        background: #f1f2f3;
        color: #606467;
    }

    .view-btn {
        display: inline-block;
        padding: 7px 12px;
        border: 1px solid #d2d5d8;
        border-radius: 6px;
        color: #202223;
        text-decoration: none;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        transition: all .2s;
    }

    .view-btn:hover {
        border-color: #008060;
        color: #008060;
        background: #f6fdfa;
    }

    /* DataTables Controls (Length, Info, Pagination) */
    .dataTables_wrapper {
        padding-top: 14px;
    }

    .dataTables_wrapper::after {
        content: "";
        display: table;
        clear: both;
    }

    .dataTables_wrapper .dataTables_length {
        color: #6d7175;
        font-size: 12px;
        margin-bottom: 14px;
    }

    .dataTables_wrapper .dataTables_length select {
        padding: 6px 10px;
        border: 1px solid #d2d5d8;
        border-radius: 6px;
        font-family: inherit;
        font-size: 12px;
        outline: none;
        background: #fff;
        margin: 0 4px;
    }

    .dataTables_wrapper .dataTables_filter {
        color: #6d7175;
        font-size: 12px;
        margin-bottom: 14px;
    }

    .dataTables_wrapper .dataTables_filter input {
        padding: 6px 12px;
        border: 1px solid #d2d5d8;
        border-radius: 6px;
        font-family: inherit;
        font-size: 12px;
        outline: none;
        margin-left: 8px;
        transition: border-color .2s;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #008060;
    }

    .dataTables_wrapper .dataTables_info {
        padding: 16px 0 6px 0;
        color: #6d7175;
        font-size: 12px;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding: 12px 0 6px 0;
        font-size: 12px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 6px 12px !important;
        margin: 0 2px !important;
        border: 1px solid #d2d5d8 !important;
        border-radius: 6px !important;
        background: #ffffff !important;
        color: #202223 !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        transition: all .2s;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f6f6f7 !important;
        color: #008060 !important;
        border-color: #008060 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #008060 !important;
        color: #ffffff !important;
        border-color: #008060 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        color: #8c9196 !important;
        border-color: #e1e3e5 !important;
        background: #fafbfb !important;
        cursor: not-allowed !important;
    }

    .dataTables_wrapper .dataTables_processing {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid #e1e3e5 !important;
        border-radius: 8px !important;
        color: #008060 !important;
        font-weight: 700 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    /* Case B: Empty search results cell inside table */
    .dataTables_empty {
        padding: 40px 16px !important;
        text-align: center !important;
        color: #6d7175 !important;
        font-size: 13px !important;
    }

    /* Case A: Zero merchants in database empty state card */
    .empty-database-card {
        background: #ffffff;
        border: 1px solid #e1e3e5;
        border-radius: 10px;
        padding: 64px 24px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
        margin-top: 10px;
    }

    .empty-database-icon-wrap {
        width: 68px;
        height: 68px;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: #e4f5ec;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #008060;
    }

    .empty-database-icon-wrap svg {
        width: 34px;
        height: 34px;
    }

    .empty-database-title {
        font-size: 17px;
        font-weight: 700;
        color: #202223;
        margin: 0 0 8px;
    }

    .empty-database-subtitle {
        font-size: 13px;
        color: #6d7175;
        margin: 0 auto;
        max-width: 480px;
        line-height: 1.5;
    }

    @media(max-width: 600px) {
        .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            float: none;
            text-align: left;
        }

        .dataTables_wrapper .dataTables_filter input {
            margin-left: 0;
            margin-top: 6px;
            width: 100%;
            box-sizing: border-box;
        }
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Merchants</h1>
        <p class="page-description">
            Manage and monitor your Socialvire app merchants.
        </p>
    </div>

    <div class="merchant-count">
        {{ $totalMerchantsCount }} Total Merchants
    </div>
</div>

@if($totalMerchantsCount === 0)
    {{-- Case A: Zero Merchants in the entire database --}}
    <div class="empty-database-card">
        <div class="empty-database-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.25A2.25 2.25 0 0 1 0 18.75V5.25A2.25 2.25 0 0 1 2.25 3h13.5A2.25 2.25 0 0 1 18 5.25v13.5A2.25 2.25 0 0 1 15.75 21m-4.5 0h4.5m0-15.75h-9a2.25 2.25 0 0 0-2.25 2.25v.75m11.25-3a2.25 2.25 0 0 1 2.25 2.25v.75m0 0v6.75m0-6.75h-11.25" />
            </svg>
        </div>
        <h2 class="empty-database-title">No merchants have installed Socialvire yet</h2>
        <p class="empty-database-subtitle">
            When a Shopify merchant installs your app, their store will automatically appear here.
        </p>
    </div>
@else
    {{-- DataTable Card --}}
    <div class="table-card">
        <div class="table-heading">
            All Merchants
        </div>

        <div class="table-wrap">
            <table id="merchants-table" class="merchant-table">
                <thead>
                    <tr>
                        <th>STORE</th>
                        <th>PLAN</th>
                        <th>ACCOUNT STATUS</th>
                        <th>SUBSCRIPTION</th>
                        <th>INSTALLED ON</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Populated dynamically via DataTables serverSide AJAX --}}
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection

@if($totalMerchantsCount > 0)
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#merchants-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('admin.merchants.index') }}",
                type: "GET"
            },
            columns: [
                { data: 'store', name: 'shop', orderable: true },
                { data: 'plan', name: 'plan', orderable: false },
                { data: 'account_status', name: 'account_status', orderable: false },
                { data: 'subscription', name: 'subscription', orderable: false },
                { data: 'installed_on', name: 'installed_at', orderable: true },
                { data: 'action', name: 'action', orderable: false }
            ],
            order: [[4, 'desc']],
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            pageLength: 10,
            language: {
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No merchants match your search or selected filters.",
                emptyTable: "No merchants match your search or selected filters.",
                search: "Search:",
                searchPlaceholder: "Search Shopify store...",
                paginate: {
                    previous: "&larr; Previous",
                    next: "Next &rarr;"
                }
            }
        });
    });
</script>
@endpush
@endif
