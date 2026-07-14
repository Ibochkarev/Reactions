<button
    type="button"
    class="reactions-widget__button[[+active:is=`1`:then=` is-active`]]"
    data-type="[[+name]]"
    data-reaction="[[+name]]"
    data-emoji="[[+emoji]]"
    aria-pressed="[[+active:is=`1`:then=`true`:else=`false`]]"
    aria-label="[[+name]][[+count:gt=`0`:then=`, [[+count]]`]]"
>
    <span class="reactions-widget__emoji" aria-hidden="true">[[+emoji]]</span>
    <span class="reactions-widget__count"[[+count:eq=`0`:then=` hidden`]]>[[+count]]</span>
</button>
