<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gia phả {{ $group->name }}</title>
    <link rel="stylesheet" href="/vendor/family-chart.css">
    @vite(['resources/css/app.css'])
    <style>
        body { margin: 0; background: #f9fafb; font-family: 'Inter', sans-serif; }
        #family-tree path.link { stroke: #6b7280 !important; stroke-width: 2px !important; opacity: 1 !important; }
        #family-tree svg.main_svg { background: #fff !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

{{-- Header --}}
<div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
    <div>
        <h1 class="text-lg font-bold text-gray-900">🌳 Gia phả {{ $group->name }}</h1>
        <p class="text-xs text-gray-400 mt-0.5">Chế độ xem — kéo để di chuyển, cuộn để zoom</p>
    </div>
    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Chỉ xem</span>
</div>

{{-- Tree --}}
<div class="flex-1" style="height: calc(100vh - 60px); position: relative; overflow: hidden;">
    <div id="family-tree" style="width:100%; height:100%;"></div>
</div>

<script src="https://unpkg.com/family-chart/dist/family-chart.js"></script>
<script>
(function() {
    const dataUrl = '{{ route('public-tree.data', $share->share_token) }}';
    const cont    = document.getElementById('family-tree');
    let f3Chart   = null;

    fetch(dataUrl, { headers: { Accept: 'application/json' } })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(initTree)
        .catch(e => {
            cont.innerHTML = `<p style="text-align:center;padding:60px 20px;color:#9ca3af;font-size:14px;">Không load được cây gia phả. (${e.message})</p>`;
        });

    function initTree(rawNodes) {
        if (!rawNodes.length) {
            cont.innerHTML = '<p style="text-align:center;padding:60px 20px;color:#9ca3af;font-size:14px;">Chưa có thành viên nào.</p>';
            return;
        }
        if (!window.f3?.createChart) {
            cont.innerHTML = '<p style="text-align:center;padding:60px 20px;color:#9ca3af;font-size:14px;">Không load được thư viện.</p>';
            return;
        }

        if (f3Chart) { try { f3Chart.destroy(); } catch(e) {} f3Chart = null; }
        cont.innerHTML = '';

        const validNodes = rawNodes.filter(n => n && n.id);
        const validIds   = new Set(validNodes.map(n => String(n.id)));

        const data = validNodes.map(n => {
            const nId = String(n.id);
            const spouseIds = (n.pids || []).map(String).filter(id => validIds.has(id));
            const children  = validNodes
                .filter(c => String(c.fid) === nId || String(c.mid) === nId)
                .map(c => String(c.id));

            return {
                id: nId,
                rels: {
                    father:   n.fid && validIds.has(String(n.fid)) ? String(n.fid) : undefined,
                    mother:   n.mid && validIds.has(String(n.mid)) ? String(n.mid) : undefined,
                    spouses:  spouseIds,
                    children,
                },
                data: {
                    'first name': n.name,
                    pronoun:     n.pronoun || '',
                    birthday:    n.birth_year ? String(n.birth_year) : '',
                    death_year:  n.death_year ? String(n.death_year) : '',
                    gender:      n.gender === 'female' ? 'F' : 'M',
                    is_alive:    !!n.is_alive,
                },
            };
        });

        f3Chart = window.f3.createChart(cont, data)
            .setTransitionTime(600)
            .setCardXSpacing(260)
            .setCardYSpacing(150)
            .setOrientationIsLeft(false)
            .setCard(window.f3.CardHtml)
            .setCardHtml(d => {
                const m         = d.data;
                const pronoun   = m.pronoun ? `<div style="font-size:11px;color:#7a5c1e;margin-bottom:2px;">${m.pronoun}</div>` : '';
                const name      = `<div style="font-weight:700;font-size:14px;color:#1f2937;line-height:1.2;">${m['first name'] || ''}</div>`;
                const lifespan  = m.is_alive
                    ? (m.birthday ? `<div style="font-size:11px;color:#6b7280;margin-top:3px;">sinh ${m.birthday}</div>` : '')
                    : (m.death_year ? `<div style="font-size:11px;color:#6b7280;margin-top:3px;">${m.birthday || ''}–${m.death_year}</div>` : '');
                const icon      = m.is_alive ? '' : '<span style="color:#ef4444;font-size:10px;margin-right:3px;">†</span>';
                return `<div style="padding:10px 14px;text-align:center;min-height:72px;display:flex;flex-direction:column;justify-content:center;">${icon}${pronoun}${name}${lifespan}</div>`;
            })
            .init();
    }
})();
</script>
</body>
</html>
