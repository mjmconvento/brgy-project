@extends('template')
@section('additional_css')
    <link rel="stylesheet" href="{!! asset('template_libraries/custom.css') !!}">    
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h1 class="box-title">Add Tax Payment</h1>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <form class="form-horizontal" method="POST">
                        <div class="box-body">
 
                            @include("Templates.number_form", ['label_size' => '4', 'label' => 'Amount', 'input_size' => '8', 'id' => 'amount', 'required' => 'required', 'value' => isset($tax) ? $tax->amount : '0'])

                            <div class="form-group">
                                <label for="status" class="col-sm-4 control-label">Status</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="status" name="status">
                                        <option value="Paid">Paid</option>
                                        <option value="Unpaid" @if(isset($tax) and $tax->status === "Unpaid") selected @endif>Unpaid</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="month" class="col-sm-4 control-label">Month</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="month" name="month">
                                        @if(isset($tax))
                                            @foreach($months as $m)
                                                <option value="{{ $m }}" @if($tax->payment_month === $m) selected @endif>{{ $m }}</option>
                                            @endforeach
                                        @else
                                            @foreach($months as $m)
                                                <option value="{{ $m }}">{{ $m }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            @include("Templates.number_form", ['label_size' => '4', 'label' => 'Year', 'input_size' => '8', 'id' => 'year', 'required' => 'required', 'value' => isset($tax) ? $tax->payment_year : '0'])

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