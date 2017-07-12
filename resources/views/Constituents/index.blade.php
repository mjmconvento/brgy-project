@extends('template')

@section('additional_css')
    <link rel="stylesheet" href="{!! asset('public/template_libraries/plugins/datatables/dataTables.bootstrap.css') !!}">  
    <link rel="stylesheet" href="{!! asset('public/template_libraries/custom.css') !!}">    
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
                        <h3 class="box-title">Constituents List</h3>
                        <a class="btn btn-block btn-success" id="add_new_btn" href="/constituent/create">
                            Add New                    
                        </a>
                    </div>
                    
                    <div class="box-body">
                        <table id="dt_constituents" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Voted Brgy. Captain</th>
                                    <th>Has Unpaid Taxes</th>
                                    <th>Has Record</th>
                                    <th>Details</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($constituents as $c)
                                <tr>
                                    <td>{{ $c->first_name }}</td>
                                    <td>{{ $c->last_name }}</td>
                                    <td>{{ $c->brgyCaptain->last_name or 'N/A' }}</td>
                                    <td>
                                        @if ($c->has_unpaid_tax)
                                            <span class="criminal-record">Has Unpaid Taxes</span> 
                                        @else
                                            None
                                        @endif
                                    </td>
                                    <td>
                                        @if ($c->has_record)
                                            <span class="criminal-record">Has Criminal Record</span> 
                                        @else
                                            None
                                        @endif
                                    </td>
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
                                        <a class="btn btn-block btn-danger delete-cons" data-id="{{ $c->id }}" type="submit">DELETE</a>

                                        <form class="hidden" action="/constituent/{{ $c->id }}" method="POST">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input class="btn btn-block btn-danger delete-hidden-cons" type="submit" value="DELETE">
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

        @include('Templates.delete_modal')

    </section>
@endsection

@section('additional_js')
    <script src="{!! asset('public/template_libraries/plugins/datatables/jquery.dataTables.min.js') !!}"></script>
    <script src="{!! asset('public/template_libraries/plugins/datatables/dataTables.bootstrap.min.js') !!}"></script>
    <script src="{!! asset('public/js/constituents.js') !!}"></script>

    
    <script>
        $(function () {
            $("#dt_constituents").DataTable();
        });
    </script>
@endsection