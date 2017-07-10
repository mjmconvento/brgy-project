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

                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'First Name', 'input_size' => '8', 'id' => 'first_name', 'value' => isset($constituent) ? $constituent->first_name : '', 'required' => 'required'])
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Middle Name', 'input_size' => '8', 'id' => 'middle_name', 'value' => isset($constituent) ? $constituent->middle_name : '' ])
                            @include("Templates.input_form", ['label_size' => '4', 'label' => 'Last Name', 'input_size' => '8', 'id' => 'last_name', 'value' => isset($constituent) ? $constituent->last_name : '', 'required' => 'required' ])
                            @include("Templates.textarea_form", ['label_size' => '4', 'label' => 'Address', 'input_size' => '8', 'id' => 'address', 'value' => isset($constituent) ? $constituent->address : '', 'required' => 'required' ])
                            
                            <div class="form-group">
                                <label for="brgy_captain" class="col-sm-4 control-label">Voted Brgy. Captain Last Election</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="brgy_captain" name="brgy_captain">
                                        <option value="0">N/A</option>
                                        @foreach($brgy_captains as $b)
                                            <option value="{{ $b->id }}" @if(isset($constituent) and $b->id == $constituent->brgy_captain_id) selected @endif>{{ $b->last_name }}, {{ $b->first_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if ($method === "edit")
                                <input type="hidden" name="_method" value="PUT">
                            @endif

                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary pull-right">Submit</button>
                            <a href="/constituents" class="btn btn-danger pull-right back_btn">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection