<button
    type="button"
    class="reactions-btn[[+active:is=`1`:then=` reactions-btn--active`]]"
    data-reaction="[[+name]]"
    data-emoji="[[+emoji]]"
    aria-pressed="[[+active:is=`1`:then=`true`:else=`false`]]"
    aria-label="[[+name]]"
>
    <span class="reactions-btn__emoji" aria-hidden="true">[[+emoji]]</span>
    <span class="reactions-btn__count">[[+count]]</span>
</button>
