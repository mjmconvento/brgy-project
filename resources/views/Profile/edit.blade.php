@extends('template')

@section('additional_css')
    <link rel="stylesheet" href="{!! asset('public/template_libraries/custom.css') !!}">    
@endsection

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h1 class="box-title">Add Constituent</h1>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <form class="form-horizontal" action="{{ $user->id }}" method="POST">
                        <div class="box-body">

                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'First Name', 'input_size' => '8', 'id' => 'first_name', 'value' => $user->first_name, 'required' => 'required'])
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Middle Name', 'input_size' => '8', 'id' => 'middle_name', 'value' => $user->middle_name, 'required' => 'required'])
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Last Name', 'input_size' => '8', 'id' => 'last_name', 'value' => $user->last_name, 'required' => 'required' ])
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Email Address', 'input_size' => '8', 'id' => 'email', 'value' => $user->email, 'required' => 'required' ])

                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="PUT">
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary pull-right">Submit</button>
                            <a href="/constituents" class="btn btn-danger pull-right back_btn">Home</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection