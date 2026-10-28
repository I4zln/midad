{{-- Shared confirmation for forms with data-confirm="…" (see app.js). --}}
<dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-message">
    <form method="dialog" class="confirm-dialog-body">
        <p id="confirm-dialog-message">هل أنت متأكد؟</p>
        <div class="confirm-dialog-actions">
            <button class="button button-outline" value="cancel" autofocus>إلغاء</button>
            <button class="button button-danger" value="confirm">نعم، احذف</button>
        </div>
    </form>
</dialog>
