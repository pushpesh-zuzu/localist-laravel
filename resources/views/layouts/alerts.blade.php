@if (session('success'))
    <div class="alert alert-success mt-3" id="alertMsg">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger mt-3" id="alertMsg">
        {{ session('error') }}
    </div>
@endif

@if (session('delete'))
    <div class="alert alert-warning mt-3" id="alertMsg">
        {{ session('delete') }}
    </div>
@endif
<script>
    setTimeout(() => {
        document.getElementById('alertMsg')?.remove();
    }, 3000);
</script>