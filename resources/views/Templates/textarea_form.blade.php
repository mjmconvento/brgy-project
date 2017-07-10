<div class="form-group">
    <label for="{{ $id }}" class="col-sm-{{ $label_size }} control-label">{{ $label }}</label>
    <div class="col-sm-{{ $input_size }}">
        <textarea class="form-control" rows="3" name="{{ $id }}" id="{{ $id }}" placeholder="{{ $label }}" {{ $required or '' }}>{{ $value or '' }}</textarea>
    </div>
</div>