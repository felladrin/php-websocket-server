var User = {
    add: function (id, name, description) {
        var userDiv = $("<li/>").attr('data-id', id).appendTo('.message-user-list');
        var a = $("<a/>", {href: "#"}).appendTo(userDiv);
        $("<span/>", {class: "user-img"}).appendTo(a);
        $("<span/>", {class: "user-title", text: name}).appendTo(a);
        $("<p/>", {class: "user-desc", text: description}).appendTo(a);
    },

    remove: function (id) {
        $(".message-user-list li[data-id='" + id + "']").remove();
    }
};