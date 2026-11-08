// Sắp xếp danh sách bằng kéo thả HOẶC nút ▲/▼ (dùng được bằng bàn phím và màn hình cảm ứng).
// Dùng cho mốc thời gian, thành viên ban, nhà tài trợ, slider... và cây menu (lồng tối đa 2 cấp).
//
// HTML cần có (template quản trị tạo sẵn):
//   <ul data-sortable data-sortable-url="/admin/dates/reorder" data-csrf="..." [data-max-depth="2"]
//       data-depth-error="Menu tối đa 2 cấp" data-saved-text="Đã lưu thứ tự">
//     <li data-sortable-item data-id="5">
//       ... <button type="button" data-move="up">▲</button> <button type="button" data-move="down">▼</button>
//           [<button type="button" data-move="in">→</button> <button type="button" data-move="out">←</button>]
//       [<ul data-sortable-children>...mục con...</ul>]
//     </li>
//   </ul>
//   <p data-sortable-status aria-live="polite"></p>
//
// Sau mỗi thay đổi, thứ tự mới được gửi lên server:
//   danh sách phẳng: ids[]=5&ids[]=3...
//   cây lồng cấp:    items[0][id]=5&items[0][parent_id]=&items[1][id]=3&items[1][parent_id]=5...
(function () {
  'use strict';

  function directItems(list) {
    return Array.prototype.filter.call(list.children, function (el) {
      return el.hasAttribute('data-sortable-item');
    });
  }

  function childList(item, create) {
    var list = item.querySelector(':scope > [data-sortable-children]');
    if (!list && create) {
      list = document.createElement('ul');
      list.setAttribute('data-sortable-children', '');
      item.appendChild(list);
    }
    return list;
  }

  function depthOf(item, root) {
    var depth = 1;
    var parent = item.parentElement;
    while (parent && parent !== root) {
      if (parent.hasAttribute('data-sortable-item')) depth++;
      parent = parent.parentElement;
    }
    return depth;
  }

  function subtreeHeight(item) {
    var children = childList(item, false);
    if (!children) return 1;
    var max = 0;
    directItems(children).forEach(function (child) { max = Math.max(max, subtreeHeight(child)); });
    return 1 + max;
  }

  function SortableList(root) {
    this.root = root;
    this.maxDepth = parseInt(root.getAttribute('data-max-depth') || '1', 10);
    this.status = document.querySelector('[data-sortable-status]');
    this.dragged = null;
    this.bind();
  }

  SortableList.prototype.say = function (text) {
    if (this.status) this.status.textContent = text;
  };

  SortableList.prototype.bind = function () {
    var self = this;

    this.root.addEventListener('click', function (event) {
      var button = event.target.closest('[data-move]');
      if (!button || !self.root.contains(button)) return;
      var item = button.closest('[data-sortable-item]');
      if (self.move(item, button.getAttribute('data-move'))) {
        button.focus();
        self.save();
      }
    });

    this.root.querySelectorAll('[data-sortable-item]').forEach(function (item) {
      item.setAttribute('draggable', 'true');
    });

    this.root.addEventListener('dragstart', function (event) {
      var item = event.target.closest('[data-sortable-item]');
      if (!item) return;
      self.dragged = item;
      item.classList.add('is-dragging');
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', item.getAttribute('data-id'));
    });

    this.root.addEventListener('dragover', function (event) {
      var target = event.target.closest('[data-sortable-item]');
      if (!self.dragged || !target || target === self.dragged || self.dragged.contains(target)) return;
      event.preventDefault();
      var rect = target.getBoundingClientRect();
      var after = event.clientY > rect.top + rect.height / 2;
      if (target.parentElement !== self.dragged.parentElement) {
        var newDepth = depthOf(target, self.root) + subtreeHeight(self.dragged) - 1;
        if (newDepth > self.maxDepth) return;
      }
      target.parentElement.insertBefore(self.dragged, after ? target.nextSibling : target);
    });

    this.root.addEventListener('dragend', function () {
      if (!self.dragged) return;
      self.dragged.classList.remove('is-dragging');
      self.dragged = null;
      self.save();
    });
  };

  /** Di chuyển một mục; trả về true nếu có thay đổi. */
  SortableList.prototype.move = function (item, direction) {
    var list = item.parentElement;
    var siblings = directItems(list);
    var index = siblings.indexOf(item);

    if (direction === 'up' && index > 0) {
      list.insertBefore(item, siblings[index - 1]);
      return true;
    }
    if (direction === 'down' && index < siblings.length - 1) {
      list.insertBefore(siblings[index + 1], item);
      return true;
    }
    if (direction === 'in' && index > 0) {
      if (depthOf(item, this.root) + subtreeHeight(item) > this.maxDepth) {
        this.say(this.root.getAttribute('data-depth-error') || '');
        return false;
      }
      childList(siblings[index - 1], true).appendChild(item);
      return true;
    }
    if (direction === 'out') {
      var parentItem = list.closest('[data-sortable-item]');
      if (!parentItem || !this.root.contains(parentItem)) return false;
      parentItem.parentElement.insertBefore(item, parentItem.nextSibling);
      return true;
    }
    return false;
  };

  SortableList.prototype.save = function () {
    var self = this;
    var data = new FormData();
    data.append('_token', this.root.getAttribute('data-csrf') || '');

    if (this.maxDepth > 1) {
      var position = 0;
      var walk = function (list, parentId) {
        directItems(list).forEach(function (item) {
          data.append('items[' + position + '][id]', item.getAttribute('data-id'));
          data.append('items[' + position + '][parent_id]', parentId);
          position++;
          var children = childList(item, false);
          if (children) walk(children, item.getAttribute('data-id'));
        });
      };
      walk(this.root, '');
    } else {
      directItems(this.root).forEach(function (item) { data.append('ids[]', item.getAttribute('data-id')); });
    }

    fetch(this.root.getAttribute('data-sortable-url'), {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch' },
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (body) {
        self.say(response.ok ? (self.root.getAttribute('data-saved-text') || '') : (body.error || self.root.getAttribute('data-error-text') || ''));
      });
    }).catch(function () {
      self.say(self.root.getAttribute('data-error-text') || '');
    });
  };

  document.querySelectorAll('[data-sortable]').forEach(function (root) { new SortableList(root); });
})();
