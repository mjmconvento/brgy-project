@extends('template')

@section('additional_css')
    <link rel="stylesheet" href="{!! asset('template_libraries/custom.css') !!}">    
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
                    <form class="form-horizontal" method="POST">
                        <div class="box-body">

                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'First Name', 'input_size' => '8', 'id' => 'first_name', 'value' => isset($brgy_captain) ? $brgy_captain->first_name : '', 'required' => 'required'])
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Middle Name', 'input_size' => '8', 'id' => 'middle_name', 'value' => isset($brgy_captain) ? $brgy_captain->middle_name : '' ])
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Last Name', 'input_size' => '8', 'id' => 'last_name', 'value' => isset($brgy_captain) ? $brgy_captain->last_name : '', 'required' => 'required' ])
                            @include("Templates.textarea_form", ['label_size' => '4', 'label' => 'Address', 'input_size' => '8', 'id' => 'address', 'value' => isset($brgy_captain) ? $brgy_captain->address : '', 'required' => 'required' ])

                            @if ($method === "edit")
                                <input type="hidden" name="_method" value="PUT">
                            @endif

                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary pull-right">Submit</button>
                            <a href="/brgy_captains" class="btn btn-danger pull-right back_btn">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection