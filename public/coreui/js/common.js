function deleteUser(id, type = 'complete', tableId = null, message = 'Are you sure you want to delete this record?') {
    if (confirm(message)) {
        var _url = $("#_url").val();
        $.ajax({
            url: _url + "/delete-user/" + id,
            type: "POST",
            data: {
                _method: "DELETE",
                _token: $('meta[name="csrf-token"]').attr('content'),
                type: type,
            },
            success: function (response) {
                alert(response.message || 'Deleted successfully');
                if (tableId && $.fn.DataTable.isDataTable('#' + tableId)) {
                    $('#' + tableId).DataTable().ajax.reload(null, false);
                } else {
                    // if tableId not provided
                    $("#userid" + id).fadeOut(400, function () {
                        $(this).remove();
                    });
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                alert("Something went wrong!");
            }
        });
    }
}
