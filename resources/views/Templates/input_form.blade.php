<div class="form-group">
    <label for="{{ $id }}" class="col-sm-{{ $label_size }} control-label">{{ $label }}</label>
    <div class="col-sm-{{ $input_size }}">
        <input type="text" class="form-control" id="{{ $id }}" name="{{ $id }}" placeholder="{{ $label }}" value="{{ $value or '' }}" {{ $required or '' }}>
    </div>
</div>
