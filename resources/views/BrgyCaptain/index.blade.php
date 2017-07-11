@extends('template')

@section('additional_css')
    <link rel="stylesheet" href="{!! asset('template_libraries/plugins/datatables/dataTables.bootstrap.css') !!}">  
    <link rel="stylesheet" href="{!! asset('template_libraries/custom.css') !!}">    
@endsection

@section('content')

    {{-- For action confirmation --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('status') }}
        </div>
    @endif

    <section class="content">
        <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Brgy. Captains List</h3>
                        <a class="btn btn-block btn-success" id="add_new_btn" href="/brgy_captain/create">
                            Add New                    
                        </a>
                    </div>

                    <div class="box-body">
                        <table id="dt_candidates" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Details</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($brgy_captains as $b)
                                <tr>
                                    <td>{{ $b->first_name }}</td>
                                    <td>{{ $b->last_name }}</td>
                                    <td>
                                        <a class="btn btn-block btn-info" href="/brgy_captain/show/{{ $b->id }}">
                                            Details
                                        </a>
                                    </td>
                                    <td>   
                                        <a class="btn btn-block btn-primary" href="/brgy_captain/{{ $b->id }}">
                                            Edit
                                        </a>
                                    </td>
                                    <td>
                                        <form action="/brgy_captain/{{ $b->id }}" method="POST">
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
            $("#dt_candidates").DataTable();
        });
    </script>
@endsection