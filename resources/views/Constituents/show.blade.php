@extends('template')
@section('additional_css')
    <link rel="stylesheet" href="{!! asset('template_libraries/plugins/datatables/dataTables.bootstrap.css') !!}"> 
    <link rel="stylesheet" href="{!! asset('template_libraries/custom.css') !!}">
@endsection
@section('content')
    {{-- For confirmation status session --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('status') }}
        </div>
    @endif

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="@if (session('active_tab') == null) active @endif">
                <a href="#tab_1" data-toggle="tab">
                    <h3>Details</h3>
                </a>
            </li>
            <li @if (session('active_tab') == 'tax') class="active" @endif>
                <a href="#tab_2" data-toggle="tab">
                    <h3>Taxes</h3>
                </a>
            </li>
            <li @if (session('active_tab') == 'criminal_record') class="active" @endif>
                <a href="#tab_3" data-toggle="tab">
                    <h3>Criminal Records</h3>
                </a>
            </li>
        </ul>
        {{-- If statements inside class are for tab manipulation when submitting --}}
        <div class="tab-content">
            <div class="tab-pane @if (session('active_tab') == null) active @endif" id="tab_1">
                @include('Constituents.details')
            </div>
            <div class="tab-pane @if (session('active_tab') == 'tax') active @endif" id="tab_2">
                @include('Constituents.taxes')
            </div>
            <div class="tab-pane @if(session('active_tab') == 'criminal_record') active @endif" id="tab_3">
                @include('Constituents.records')
            </div>
        </div>
    </div>
    <a class="btn btn-block btn-danger btn-default-size" href="/constituents">
        Back
    </a>
@endsection

@section('additional_js')
    <script src="{!! asset('template_libraries/plugins/datatables/jquery.dataTables.min.js') !!}"></script>
    <script src="{!! asset('template_libraries/plugins/datatables/dataTables.bootstrap.min.js') !!}"></script>
    
    <script>
        $(function () {
            $("#dt_taxes").DataTable();
        });
    </script>
@endsection
