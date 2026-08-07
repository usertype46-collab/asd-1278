// Cloudflare Pages / Edge Route: /api/therapists

export async function onRequestGet(context) {
    const { env } = context;
    try {
        const { results } = await env.DB.prepare("SELECT * FROM therapists ORDER BY id DESC").all();
        
        const formatted = results.map(t => ({
            ...t,
            skills: JSON.parse(t.skills || '[]')
        }));

        return new Response(JSON.stringify(formatted), {
            headers: { "Content-Type": "application/json" }
        });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}

export async function onRequestPost(context) {
    const { request, env } = context;
    const body = await request.json();
    const { code, name, phone, gender, skills } = body;

    try {
        let finalCode = code;
        
        // 若前端沒有傳入自訂 code 或為空，則啟用自動遞增編號邏輯
        if (!finalCode || finalCode.trim() === '') {
            const maxIdResult = await env.DB.prepare("SELECT MAX(id) as maxId FROM therapists").first();
            const nextId = (maxIdResult.maxId || 0) + 1;
            finalCode = `T${String(nextId).padStart(3, '0')}`; // 產生如 T001, T002 格式
        }

        const insertResult = await env.DB.prepare(
            "INSERT INTO therapists (code, name, phone, gender, skills, status) VALUES (?, ?, ?, ?, ?, ?) RETURNING id"
        ).bind(finalCode, name, phone, gender || 'M', JSON.stringify(skills || []), 'active').first();

        return new Response(JSON.stringify({ success: true, id: insertResult.id, code: finalCode }), { status: 201 });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}

export async function onRequestPut(context) {
    const { request, env } = context;
    const body = await request.json();
    const { id, code, name, phone, skills, status, serviceEndTime } = body;

    try {
        // 即時同步技師的狀態、排班與倒數時間
        await env.DB.prepare(
            "UPDATE therapists SET code = ?, name = ?, phone = ?, skills = ?, status = ?, service_end_time = ? WHERE id = ?"
        ).bind(code, name, phone, JSON.stringify(skills || []), status || 'active', serviceEndTime || null, id).run();

        return new Response(JSON.stringify({ success: true }), { status: 200 });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}

export async function onRequestDelete(context) {
    const { request, env } = context;
    const url = new URL(request.url);
    const id = url.searchParams.get('id');

    try {
        // 永久刪除離職技師
        await env.DB.prepare("DELETE FROM therapists WHERE id = ?").bind(id).run();
        return new Response(JSON.stringify({ success: true }), { status: 200 });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}
