<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap km-products-wrap">
    
    <div class="km-header-bar">
        <div>
            <h1>🏷️ Catálogo de Produtos Comerciais & Kits</h1>
            <p>Formate produtos prontos para venda combinando fichas culinárias, embalagens, flores e papéis estratégicos.</p>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-products&action=new')); ?>" class="button button-primary button-large">
                ➕ Criar Novo Produto / Kit
            </a>
        </div>
    </div>

    <?php if ($status === 'saved'): ?>
        <div class="notice notice-success is-dismissible" style="margin-top: 15px;"><p><strong>✅ Produto salvo com sucesso!</strong></p></div>
    <?php elseif ($status === 'deleted'): ?>
        <div class="notice notice-info is-dismissible" style="margin-top: 15px;"><p><strong>🗑️ Produto removido com sucesso.</strong></p></div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="km-empty-card">
            <div style="font-size: 40px; margin-bottom: 10px;">🏷️</div>
            <h2>Nenhum produto cadastrado ainda</h2>
            <p>Combine suas receitas com embalagens e flores para criar produtos comerciais ou kits presenteáveis.</p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-products&action=new')); ?>" class="button button-primary">
                Cadastrar Primeiro Produto
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 16px; border-radius: 8px; overflow: hidden;">
            <thead>
                <tr>
                    <th style="width: 22%;">Nome do Produto</th>
                    <th style="width: 24%;">Composição (Receitas + Embalagens)</th>
                    <th style="width: 12%;">Custo Direto</th>
                    <th style="width: 12%;">Preço de Venda</th>
                    <th style="width: 14%;">Papel Estratégico</th>
                    <th style="width: 10%;">Margem Real</th>
                    <th style="text-align: right; width: 140px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-products&action=edit&id=' . $p->id)); ?>" style="font-size: 14px;">
                                <?php echo esc_html($p->name); ?>
                            </a></strong>
                        </td>
                        <td>
                            <small style="color: #50575e; line-height: 1.4; display: block;">
                                <?php echo esc_html($p->composition_summary ?: '— Sem itens vinculados —'); ?>
                            </small>
                        </td>
                        <td><strong>R$ <?php echo number_format($p->total_cost, 2, ',', '.'); ?></strong></td>
                        <td>
                            <?php if ($p->sale_price > 0): ?>
                                <strong style="color: #2271b1; font-size: 14px;">R$ <?php echo number_format($p->sale_price, 2, ',', '.'); ?></strong>
                            <?php else: ?>
                                <span style="color: #8c8f94;">Não definido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="km-role-badge <?php echo esc_attr($p->role_data['badge_class']); ?>">
                                <?php echo esc_html($p->role_data['label']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p->sale_price > 0): ?>
                                <strong style="color: <?php echo $p->current_margin >= $p->target_margin ? '#007017' : '#b32d2e'; ?>;">
                                    <?php echo number_format($p->current_margin, 1, ',', '.'); ?>%
                                </strong>
                                <small style="color: #646970;">(Meta: <?php echo (float) $p->target_margin; ?>%)</small>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-pricing&cost=' . $p->total_cost . '&recipe_name=' . urlencode($p->name))); ?>" class="button button-small" title="Simular no Precificador">
                                🧭
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=kitchen-manager-products&action=edit&id=' . $p->id)); ?>" class="button button-small">
                                ✏️
                            </a>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=km_delete_product&id=' . $p->id), 'km_delete_product_' . $p->id)); ?>" 
                               class="button button-small" 
                               style="color: #b32d2e;"
                               onclick="return confirm('Deseja excluir este produto?');">
                                🗑️
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.km-products-wrap { max-width: 1240px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.km-header-bar { background: #ffffff; padding: 16px 20px; border: 1px solid #ccd0d4; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
.km-header-bar h1 { margin: 0 0 4px 0; font-size: 22px; color: #1d2327; }
.km-header-bar p { margin: 0; color: #646970; font-size: 13px; }
.km-empty-card { background: #fff; border: 1px dashed #c3c4c7; padding: 40px; text-align: center; border-radius: 8px; margin-top: 20px; }

.km-role-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.km-badge-lead    { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
.km-badge-anchor  { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
.km-badge-premium { background: #f3e5f5; color: #7b1fa2; border: 1px solid #e1bee7; }
.km-badge-addon   { background: #fff8e1; color: #f57f17; border: 1px solid #ffecb3; }
</style>