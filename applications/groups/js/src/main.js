function checkIfGroupInvitesAreEmpty() {
    var $list = $('.group-invites');
    if ($list.find('.Item').length === 0) {
        $list.find('.group-invites-emptyMessage').removeClass('group-invites-emptyMessage');
    }
}
