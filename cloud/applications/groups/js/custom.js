function checkIfGroupInvitesAreEmpty() {
    var $list = $('.group-invites');
    if ($list.find('.Item').length === 0) {
        $list.remove();
    }
}
