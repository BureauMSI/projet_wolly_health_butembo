const wrap = document.querySelector('.mlm-tree-scroll');
if (wrap) {
    const mode = wrap.dataset.treeMode || 'admin';
    const near = Number(wrap.dataset.eqNear || 4);
    const after = Number(wrap.dataset.eqAfter || 1);
    const labelTemplate = wrap.dataset.eqLabel || 'Équilibre n° __N__';
    const sourceLabel = wrap.dataset.eqSourceLabel || '';
    const rootId = wrap.dataset.rootId || '';
    const detail = document.getElementById('member-tree-detail');
    const pathList = document.getElementById('member-tree-detail-path');
    const drillLink = document.getElementById('member-tree-detail-drill');
    const tree = wrap.querySelector('.mlm-tree');
    let zoom = 1;

    const isPhone = () => window.matchMedia('(max-width: 991.98px)').matches;

    const applyZoom = () => {
        if (!tree) {
            return;
        }
        tree.style.transform = `scale(${zoom})`;
        centerTree();
    };

    const centerTree = () => {
        wrap.scrollLeft = Math.max(0, (wrap.scrollWidth - wrap.clientWidth) / 2);
    };

    const fitReadable = () => {
        if (!tree || !isPhone()) {
            zoom = 1;
            if (tree) {
                tree.style.transform = '';
            }
            centerTree();
            return;
        }
        tree.style.transform = 'scale(1)';
        const available = Math.max(0, wrap.clientWidth - 8);
        const needed = Math.max(tree.scrollWidth, tree.offsetWidth);
        if (needed > 0 && available > 0) {
            zoom = Math.min(1, Math.max(0.35, (available / needed) * 0.98));
        } else {
            zoom = 0.65;
        }
        applyZoom();
    };

    fitReadable();
    window.addEventListener('load', fitReadable);
    window.addEventListener('resize', fitReadable);

    document.querySelectorAll('[data-tree-zoom]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const action = btn.getAttribute('data-tree-zoom');
            if (action === 'in') {
                zoom = Math.min(1.2, zoom + 0.08);
            } else if (action === 'out') {
                zoom = Math.max(0.3, zoom - 0.08);
            } else {
                fitReadable();
                return;
            }
            applyZoom();
        });
    });

    const clearEq = () => {
        wrap.querySelectorAll('.mlm-eq-link, .mlm-eq-source').forEach((el) => {
            el.classList.remove('mlm-eq-link', 'mlm-eq-link-near', 'mlm-eq-link-far', 'mlm-eq-source');
        });
        wrap.querySelectorAll('.mlm-node-eq').forEach((el) => el.classList.remove('mlm-node-eq'));
        wrap.querySelectorAll('.mlm-node-self').forEach((el) => {
            if (!el.dataset.keepSelf) {
                el.classList.remove('mlm-node-self');
            }
        });
        wrap.querySelectorAll('.mlm-node-selected').forEach((el) => el.classList.remove('mlm-node-selected'));
        wrap.querySelectorAll('.mlm-eq-badge').forEach((badge) => {
            if (!badge.closest('[data-keep-eq]')) {
                badge.remove();
            }
        });
        wrap.querySelectorAll('[data-eq-level]').forEach((el) => {
            if (!el.hasAttribute('data-keep-eq')) {
                el.removeAttribute('data-eq-level');
            }
        });
        if (pathList) {
            pathList.innerHTML = '';
            pathList.hidden = true;
        }
    };

    const fillBaseDetail = (node, genText) => {
        if (!detail) {
            return;
        }
        const labelLeft = detail.dataset.labelLeft || 'Gauche';
        const labelRight = detail.dataset.labelRight || 'Droite';
        document.getElementById('member-tree-detail-name').textContent = node.dataset.name || '';
        document.getElementById('member-tree-detail-code').textContent = node.dataset.code || '';
        document.getElementById('member-tree-detail-gen').textContent = genText;
        document.getElementById('member-tree-detail-left').textContent =
            `${labelLeft} : ${node.dataset.leftPv || '0.00'} PV`;
        document.getElementById('member-tree-detail-right').textContent =
            `${labelRight} : ${node.dataset.rightPv || '0.00'} PV`;
        detail.hidden = false;

        if (drillLink) {
            const drillUrl = node.dataset.drillUrl || '';
            const isViewRoot = String(node.dataset.memberId) === String(rootId)
                || Number(node.dataset.generation) === 0;
            if (drillUrl && !isViewRoot) {
                drillLink.href = drillUrl;
                drillLink.hidden = false;
            } else {
                drillLink.hidden = true;
            }
        }
    };

    const showMemberDetail = (node) => {
        wrap.querySelectorAll('.mlm-node-selected').forEach((el) => el.classList.remove('mlm-node-selected'));
        node.classList.add('mlm-node-selected');

        const generation = node.dataset.generation !== undefined && node.dataset.generation !== ''
            ? Number(node.dataset.generation)
            : null;
        const labelGen = detail?.dataset.labelGeneration || 'Génération';
        const labelYou = detail?.dataset.labelYou || 'Vous';

        let genText = labelGen;
        if (generation === 0 || String(node.dataset.memberId) === String(rootId)) {
            genText = `${labelGen} : 0 (${labelYou})`;
        } else if (generation !== null && !Number.isNaN(generation)) {
            genText = `${labelGen} : ${generation}`;
        }

        fillBaseDetail(node, genText);
        detail?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const paintPath = (sourceId) => {
        clearEq();
        const byId = {};
        wrap.querySelectorAll('.mlm-node[data-member-id]').forEach((node) => {
            byId[node.dataset.memberId] = node;
        });

        const source = byId[String(sourceId)];
        if (!source) {
            return;
        }

        source.classList.add('mlm-node-self', 'mlm-node-selected');
        source.dataset.keepSelf = '1';
        const sourceLi = source.closest('li[data-member-li]');
        if (sourceLi) {
            sourceLi.classList.add('mlm-eq-source');
        }

        const phone = isPhone();
        const pathRows = [];

        if (!phone && sourceLabel) {
            const chip = document.createElement('span');
            chip.className = 'mlm-eq-badge mlm-eq-badge-source';
            chip.textContent = sourceLabel;
            source.insertBefore(chip, source.firstChild);
        }

        let childNode = source;
        let currentId = source.dataset.parentId;
        let level = 1;
        while (currentId && level <= 32) {
            const parentNode = byId[String(currentId)];
            if (!parentNode) {
                break;
            }

            const amount = level <= 4 ? near : after;
            const childLi = childNode.closest('li[data-member-li]');
            if (childLi) {
                childLi.classList.add('mlm-eq-link', level <= 4 ? 'mlm-eq-link-near' : 'mlm-eq-link-far');
            }

            parentNode.classList.add('mlm-node-eq');
            parentNode.dataset.eqLevel = String(level);

            if (!phone) {
                const badge = document.createElement('span');
                badge.className = `mlm-eq-badge ${level <= 4 ? 'mlm-eq-badge-near' : 'mlm-eq-badge-far'}`;
                badge.textContent = `${labelTemplate.replace('__N__', String(level))} · ${amount}$`;
                parentNode.insertBefore(badge, parentNode.firstChild);
            }

            pathRows.push({
                level,
                amount,
                name: parentNode.dataset.name || parentNode.querySelector('.mlm-name')?.textContent || '',
                near: level <= 4,
            });

            childNode = parentNode;
            currentId = parentNode.dataset.parentId;
            level += 1;
        }

        const labelPath = detail?.dataset.labelPath || 'Équilibres';
        fillBaseDetail(source, `${labelPath} · ${sourceLabel || 'Source'}`);

        if (pathList && pathRows.length) {
            pathList.innerHTML = pathRows.map((row) => (
                `<li class="${row.near ? 'is-near' : 'is-far'}">`
                + `<span>${labelTemplate.replace('__N__', String(row.level))} · ${row.name}</span>`
                + `<strong>${row.amount}$</strong>`
                + `</li>`
            )).join('');
            pathList.hidden = false;
        }

        if (detail) {
            detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    const drillTo = (node) => {
        const url = node.dataset.drillUrl;
        if (url) {
            window.location.href = url;
        }
    };

    const onSelect = (node) => {
        const isViewRoot = String(node.dataset.memberId) === String(rootId)
            || Number(node.dataset.generation) === 0;

        // Drill into deeper generations on click (except current view root)
        if (!isViewRoot && node.dataset.drillUrl) {
            drillTo(node);
            return;
        }

        if (mode === 'admin') {
            paintPath(node.dataset.memberId);
            return;
        }

        showMemberDetail(node);
    };

    wrap.addEventListener('click', (event) => {
        const node = event.target.closest('.mlm-node[data-member-id]');
        if (!node || node.classList.contains('mlm-node-empty')) {
            return;
        }
        event.preventDefault();
        onSelect(node);
    });

    wrap.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }
        const node = event.target.closest('.mlm-node[data-member-id]');
        if (!node || node.classList.contains('mlm-node-empty')) {
            return;
        }
        event.preventDefault();
        onSelect(node);
    });
}
