<div class="form-group">
<label for="{{ $id }}" class="col-sm-{{ $label_size }} control-label">{{ $label }} {{ $value }}</label>
	<div class="col-sm-{{ $input_size }}">
		<input type="date" name="{{ $id }}" id="{{ $id }}" value="{{ $value }}">
	</div>
</div>
