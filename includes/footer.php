
</div><!-- /.main-wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Global JS -->
<script src="<?= base_url('assets/js/main.js') ?>"></script>

<?php if (isset($extra_js) && is_array($extra_js)): ?>
<?php foreach ($extra_js as $js): ?>
<script src="<?= base_url($js) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>

<?php if (isset($inline_js)): ?>
<script><?= $inline_js ?></script>
<?php endif; ?>

</body>
</html>
