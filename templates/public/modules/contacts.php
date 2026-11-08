<?php
/**
 * Email chung của hội thảo và thẻ người liên hệ (module contacts).
 *
 * @var \App\Models\Conference $conference
 * @var list<\App\Models\ContactPerson> $contacts
 */
?>
<?php if (($conference->contactEmail ?? '') !== ''): ?>
    <p class="contact-email"><strong><?= e(t('common.email')) ?>:</strong>
        <a href="mailto:<?= e($conference->contactEmail) ?>"><?= e($conference->contactEmail) ?></a></p>
<?php endif; ?>
<?php if ($contacts === []): ?>
    <p class="empty-note"><?= e(t('common.updating')) ?></p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($contacts as $contact): ?>
            <article class="card contact-card">
                <h2 class="card__title"><?= e($contact->fullName) ?></h2>
                <?php if (($contact->affiliation ?? '') !== ''): ?>
                    <p><?= e($contact->affiliation) ?></p>
                <?php endif; ?>
                <?php if (($contact->email ?? '') !== ''): ?>
                    <p><?= e(t('common.email')) ?>: <a href="mailto:<?= e($contact->email) ?>"><?= e($contact->email) ?></a></p>
                <?php endif; ?>
                <?php if (($contact->phone ?? '') !== ''): ?>
                    <p><?= e(t('common.phone')) ?>: <a href="tel:<?= e($contact->phoneHref()) ?>"><?= e($contact->phone) ?></a></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
