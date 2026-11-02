{{-- Champ mot de passe : œil, règles en direct, générateur. $prefix = identifiant unique. --}}
<div class="password-field">
    <input type="password" name="password" id="{{ $prefix }}Password" class="form-control w-100 js-pwd-input"
           autocomplete="new-password" required>
    <button type="button" class="password-toggle js-toggle-pwd" data-target="{{ $prefix }}Password" tabindex="-1" aria-label="Afficher le mot de passe">
        <i class="bi bi-eye"></i>
    </button>
</div>
<ul class="pwd-rules" data-for="{{ $prefix }}Password">
    <li data-rule="length">8 caractères minimum</li>
    <li data-rule="upper">Une majuscule</li>
    <li data-rule="digit">Un chiffre</li>
    <li data-rule="special">Un caractère spécial (@ # $ ! %…)</li>
</ul>
<button type="button" class="btn-tiba-secondary js-gen-pwd" data-target="{{ $prefix }}Password"
        style="padding:.3rem .8rem;font-size:.8rem;">
    <i class="bi bi-magic me-1"></i> Générer un mot de passe
</button>
