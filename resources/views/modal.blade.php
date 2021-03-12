<div id="modal" class="hide">
    <div class="modal-container">
        <div class="modal-title hide"></div>
        <div class="modal-text hide"></div>
        <div class="modal-buttons hide">
            <button class="modal-button1 hide"></button>
            <button class="modal-button2 hide"></button>
        </div>
        <button class="modal-close-button" onclick="modal('close')">✕</button>
    </div>
</div>

<datalist id="masters">
    @foreach ($Users as $item)
        <option value="{{ $item->id }}">{{ $item->name }}</option>
    @endforeach
</datalist>
