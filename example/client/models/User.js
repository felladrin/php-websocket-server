var User = {
    add: function (id, name) {
        var userDiv = $("<li/>").attr('data-id', id).appendTo('.message-user-list');
        var a = $("<a/>").appendTo(userDiv);
        $("<span/>", {class: "user-img"}).appendTo(a);
        $("<span/>", {class: "user-title", text: name}).appendTo(a);
        $("<p/>", {class: "user-desc", text: 'Online'}).appendTo(a);
    },

    remove: function (id) {
        $(".message-user-list li[data-id='" + id + "']").remove();
    }
};