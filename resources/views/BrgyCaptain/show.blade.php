@extends('template')
@section('additional_css')
    <link rel="stylesheet" href="{!! asset('public/template_libraries/plugins/datatables/dataTables.bootstrap.css') !!}">  
    <link rel="stylesheet" href="{!! asset('public/template_libraries/custom.css') !!}">    
@endsection

@section('content')
    <h1>Details</h1>
    <hr/>
    <h4>First Name: <a href="#" class="name">{{ $brgy_captain->first_name }}</a></h4>
    <h4>Middle Name: <a href="#" class="name">{{ $brgy_captain->middle_name }}</a></h4>
    <h4>Last Name: <a href="#" class="name">{{ $brgy_captain->last_name }}</a></h4>
    <h4>Address: <a href="#" class="name">{{ $brgy_captain->address }}</a></h4>
    <hr/>
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">Voters List</h3>
        </div>
        <div class="box-body">
            <table id="dt_voters" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Last Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($brgy_captain->constituents as $c)
                    <tr>
                        <td>{{ $c->first_name }}</td>
                        <td>{{ $c->middle_name }}</td>
                        <td>{{ $c->last_name }}</td>
                        <td>{{ $c->address }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a class="btn btn-block btn-danger btn-default-size" href="/brgy_captains">
        Back
    </a>
@endsection

@section('additional_js')
    <script src="{!! asset('public/template_libraries/plugins/datatables/jquery.dataTables.min.js') !!}"></script>
    <script src="{!! asset('public/template_libraries/plugins/datatables/dataTables.bootstrap.min.js') !!}"></script>
    <script>
        $(function () {
            $("#dt_voters").DataTable();
        });
    </script>
@endsection