@extends('template')
@section('additional_css')
    <link rel="stylesheet" href="{!! asset('template_libraries/custom.css') !!}">    
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h1 class="box-title">Add Criminal Record</h1>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <form class="form-horizontal" method="POST">
                        <div class="box-body">
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Case', 'input_size' => '8', 'id' => 'case', 'required' => 'required', 'value' => isset($criminal_record) ? $criminal_record->case_name : ''])
                            
                            @include("Templates.textarea_form", ['label_size' => '4', 'label' => 'Details', 'input_size' => '8', 'id' => 'details', 'value' => isset($criminal_record) ? $criminal_record->details : '' ])

                            @include("Templates.date_form", ['label_size' => '4', 'label' => 'Date', 'input_size' => '8', 'id' => 'date_execution', 'value' => isset($criminal_record) ? date('Y-m-d', strtotime($criminal_record->execution_date)) : '' ])

                            @include("Templates.time_form", ['label_size' => '4', 'label' => 'Time', 'input_size' => '8', 'id' => 'time_execution', 'value' => isset($criminal_record) ? date('H:i', strtotime($criminal_record->execution_date)) : '' ])

                            @if ($method === "edit")
                                <input type="hidden" name="_method" value="PUT">
                            @endif

                            <input type="hidden" name="cons_id" value="{{ $cons_id }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary pull-right">Submit</button>
                            <a href="/constituent/show/{{ $cons_id }}" class="btn btn-danger pull-right back_btn">Back</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection