<div class="form-group">
    <label for="first_name" class="col-sm-{{ $label_size }} control-label">{{ $label }}</label>
    <div class="col-sm-{{ $input_size }}">
        <input type="text" class="form-control" id="{{ $id }}" name="{{ $id }}" placeholder="{{ $label }}" value="{{ $value or '' }}">
    </div>
</div>
