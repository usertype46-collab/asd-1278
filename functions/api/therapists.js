// Cloudflare Edge Route: /api/therapists
export async function onRequestGet(context) {
    const { env } = context;
    try {
        // 從 Cloudflare D1 讀取技師列表
        const { results } = await env.DB.prepare("SELECT * FROM therapists ORDER BY id DESC").all();
        
        // 轉換 JSON 儲存的技能標籤
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

    const { name, phone, gender, skills } = body;

    await env.DB.prepare(
        "INSERT INTO therapists (name, phone, gender, skills) VALUES (?, ?, ?, ?)"
    ).bind(name, phone, gender || 'M', JSON.stringify(skills)).run();

    return new Response(JSON.stringify({ success: true }), { status: 201 });
}
