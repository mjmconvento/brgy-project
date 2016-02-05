@extends('template')

@section('additional_css')
    <link rel="stylesheet" href="{!! asset('template_libraries/plugins/datatables/dataTables.bootstrap.css') !!}">  
    <link rel="stylesheet" href="{!! asset('template_libraries/custom.css') !!}">    
@endsection

@section('content')
    <section class="content">
        <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Constituents List</h3>
                        <a class="btn btn-block btn-success" id="add_new_btn" href="/constituent/create">
                            Add New                    
                        </a>
                    </div>
                    <div class="box-body">
                        <table id="dt_constituents" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
                                    <th>Address</th>
                                    <th>Details</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($constituents as $c)
                                <tr>
                                    <td>{{ $c->first_name }}</td>
                                    <td>{{ $c->middle_name }}</td>
                                    <td>{{ $c->last_name }}</td>
                                    <td>{{ $c->address }}</td>
                                    <td>
                                        <a class="btn btn-block btn-info" href="/constituent/show/{{ $c->id }}">
                                            Details
                                        </a>
                                    </td>
                                    <td>   
                                        <a class="btn btn-block btn-primary" href="/constituent/{{ $c->id }}">
                                            Edit
                                        </a>
                                    </td>
                                    <td>
                                        <form action="/constituent/delete/{{ $c->id }}" method="POST">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input class="btn btn-block btn-danger" type="submit" value="DELETE">
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('additional_js')
    <script src="{!! asset('template_libraries/plugins/datatables/jquery.dataTables.min.js') !!}"></script>
    <script src="{!! asset('template_libraries/plugins/datatables/dataTables.bootstrap.min.js') !!}"></script>
    
    <script>
        $(function () {
            $("#dt_constituents").DataTable();
        });
    </script>
@endsection