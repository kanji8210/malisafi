<?php
if (!defined('ABSPATH')) {
    exit;
}

$default_currency = get_option('malisafi_mls_currency', 'USD');
if (!is_array($plans)) {
    $plans = array();
}
?>
<div class="wrap">
    <h1><?php _e('Plans Management', 'malisafi-mls'); ?></h1>
    
    <div class="notice notice-info">
        <p>
            <strong><?php _e('Currency Selection:', 'malisafi-mls'); ?></strong> 
            <?php _e('Choose between USD ($) or KES (KSh) for each plan. The currency symbol will be displayed automatically on your pricing pages.', 'malisafi-mls'); ?>
        </p>
    </div>

    <form method="post">
        <?php wp_nonce_field('malisafi_plans_save', '_malisafi_plans_nonce'); ?>
        <input type="hidden" name="malisafi_plans_action" value="save_all" />

        <div id="plans-list">
            <?php if (empty($plans)): ?>
                <p><?php _e('No plans defined. Use the form below to add a new plan.', 'malisafi-mls'); ?></p>
            <?php endif; ?>

            <?php foreach ($plans as $key => $p): ?>
                <?php $uid = esc_attr($key); ?>
                <div class="plan-block" data-key="<?php echo $uid; ?>" style="border:1px solid #ddd;padding:12px;margin-bottom:12px;">
                    <h2 style="margin-top:0;"><?php echo esc_html($p['name']); ?> <small>(<?php echo $uid; ?>)</small></h2>
                    <p>
                        <label><?php _e('Key', 'malisafi-mls'); ?>: </label>
                        <input type="text" name="plans[<?php echo $uid; ?>][key]" value="<?php echo $uid; ?>" readonly />
                    </p>
                    <p>
                        <label><?php _e('Name', 'malisafi-mls'); ?>: </label>
                        <input type="text" name="plans[<?php echo $uid; ?>][name]" value="<?php echo esc_attr($p['name']); ?>" />
                    </p>
                    <p>
                        <label><?php _e('Price', 'malisafi-mls'); ?>: </label>
                        <input type="text" name="plans[<?php echo $uid; ?>][price]" value="<?php echo esc_attr($p['price']); ?>" style="width:150px;" />
                        <label style="margin-left:8px;"><?php _e('Currency', 'malisafi-mls'); ?>: </label>
                        <select name="plans[<?php echo $uid; ?>][currency]" style="width:100px;">
                            <?php 
                            $current_currency = !empty($p['currency']) ? $p['currency'] : $default_currency;
                            ?>
                            <option value="USD" <?php selected($current_currency, 'USD'); ?>>USD ($)</option>
                            <option value="KES" <?php selected($current_currency, 'KES'); ?>>KES (KSh)</option>
                        </select>
                    </p>
                    <p>
                        <label><?php _e('Interval', 'malisafi-mls'); ?>: </label>
                        <select name="plans[<?php echo $uid; ?>][interval]">
                            <option value="month" <?php selected(isset($p['interval'])?$p['interval']:'month','month'); ?>><?php _e('Monthly', 'malisafi-mls'); ?></option>
                            <option value="year" <?php selected(isset($p['interval'])?$p['interval']:'month','year'); ?>><?php _e('Yearly', 'malisafi-mls'); ?></option>
                        </select>
                    </p>
                    <p>
                        <label><?php _e('Features (comma separated)', 'malisafi-mls'); ?>: </label>
                        <input type="text" name="plans[<?php echo $uid; ?>][features]" value="<?php echo esc_attr(is_array($p['features'])?implode(', ', $p['features']):$p['features']); ?>" style="width:70%;" />
                    </p>
                    <p>
                        <label><?php _e('Stripe Price ID', 'malisafi-mls'); ?>: </label>
                        <input type="text" name="plans[<?php echo $uid; ?>][stripe_price_id]" value="<?php echo esc_attr(!empty($p['stripe_price_id'])?$p['stripe_price_id']:''); ?>" />
                    </p>
                    <p>
                        <button class="button button-secondary remove-plan" type="button"><?php _e('Delete Plan', 'malisafi-mls'); ?></button>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <h2><?php _e('Add New Plan', 'malisafi-mls'); ?></h2>
        <div id="new-plan" style="border:1px solid #ddd;padding:12px;margin-bottom:12px;">
            <p>
                <label><?php _e('Key (unique)', 'malisafi-mls'); ?>: </label>
                <input type="text" id="new_key" />
            </p>
            <p>
                <label><?php _e('Name', 'malisafi-mls'); ?>: </label>
                <input type="text" id="new_name" />
            </p>
            <p>
                <label><?php _e('Price', 'malisafi-mls'); ?>: </label>
                <input type="text" id="new_price" style="width:150px;" />
                <label style="margin-left:8px;"><?php _e('Currency', 'malisafi-mls'); ?>: </label>
                <select id="new_currency" style="width:100px;">
                    <option value="USD" <?php selected($default_currency, 'USD'); ?>>USD ($)</option>
                    <option value="KES" <?php selected($default_currency, 'KES'); ?>>KES (KSh)</option>
                </select>
            </p>
            <p>
                <label><?php _e('Interval', 'malisafi-mls'); ?>: </label>
                <select id="new_interval">
                    <option value="month"><?php _e('Monthly', 'malisafi-mls'); ?></option>
                    <option value="year"><?php _e('Yearly', 'malisafi-mls'); ?></option>
                </select>
            </p>
            <p>
                <label><?php _e('Features (comma separated)', 'malisafi-mls'); ?>: </label>
                <input type="text" id="new_features" style="width:70%;" />
            </p>
            <p>
                <label><?php _e('Stripe Price ID', 'malisafi-mls'); ?>: </label>
                <input type="text" id="new_stripe" />
            </p>
            <p>
                <button id="add-plan" class="button button-primary" type="button"><?php _e('Add Plan', 'malisafi-mls'); ?></button>
            </p>
        </div>

        <p>
            <button class="button button-primary" type="submit"><?php _e('Save Plans', 'malisafi-mls'); ?></button>
            <button class="button" type="submit" name="malisafi_plans_action" value="reset_defaults" onclick="return confirm('Are you sure? This will remove custom plans and restore defaults.');"><?php _e('Reset to Defaults', 'malisafi-mls'); ?></button>
        </p>
    </form>
</div>

<script>
(function(){
    function createPlanBlock(key, data) {
        var wrapper = document.createElement('div');
        wrapper.className = 'plan-block';
        wrapper.setAttribute('data-key', key);
        wrapper.style = 'border:1px solid #ddd;padding:12px;margin-bottom:12px;';
        wrapper.innerHTML = '<h2 style="margin-top:0;">'+(data.name||'New')+' <small>('+key+')</small></h2>'+
            '<p><label>Key: </label><input type="text" name="plans['+key+'][key]" value="'+key+'" readonly /></p>'+
            '<p><label>Name: </label><input type="text" name="plans['+key+'][name]" value="'+(data.name||'')+'"/> </p>'+
            '<p><label>Price: </label><input type="text" name="plans['+key+'][price]" value="'+(data.price||'')+'"/> <label style="margin-left:8px;">Currency: </label><select name="plans['+key+'][currency]"><option value="USD" '+(data.currency==='USD'?'selected':'')+'>USD ($)</option><option value="KES" '+(data.currency==='KES'?'selected':'')+'>KES (KSh)</option></select></p>'+
            '<p><label>Interval: </label><select name="plans['+key+'][interval]"><option value="month">Monthly</option><option value="year">Yearly</option></select></p>'+
            '<p><label>Features (comma separated): </label><input type="text" name="plans['+key+'][features]" value="'+(data.features? (Array.isArray(data.features)?data.features.join(', '):data.features) : '')+'" style="width:70%;" /></p>'+
            '<p><label>Stripe Price ID: </label><input type="text" name="plans['+key+'][stripe_price_id]" value="'+(data.stripe_price_id||'')+'" /></p>'+
            '<p><button class="button button-secondary remove-plan" type="button">Delete Plan</button></p>';
        return wrapper;
    }

    document.getElementById('add-plan').addEventListener('click', function(e){
        var key = document.getElementById('new_key').value.trim();
        if (!key) {
            alert('Please provide a unique key for the plan');
            return;
        }
        key = key.replace(/[^a-z0-9_\-]/gi, '_').toLowerCase();
        var data = {
            name: document.getElementById('new_name').value,
            price: document.getElementById('new_price').value,
            currency: document.getElementById('new_currency').value,
            interval: document.getElementById('new_interval').value,
            features: document.getElementById('new_features').value,
            stripe_price_id: document.getElementById('new_stripe').value
        };

        // Prevent duplicate keys
        if (document.querySelector('.plan-block[data-key="'+key+'"]')) {
            alert('A plan with that key already exists. Choose another key.');
            return;
        }

        var block = createPlanBlock(key, data);
        document.getElementById('plans-list').appendChild(block);

        // Clear new form
        document.getElementById('new_key').value = '';
        document.getElementById('new_name').value = '';
        document.getElementById('new_price').value = '';
        document.getElementById('new_features').value = '';
        document.getElementById('new_stripe').value = '';
    });

    document.addEventListener('click', function(e){
        if (e.target && e.target.classList && e.target.classList.contains('remove-plan')) {
            var block = e.target.closest('.plan-block');
            if (block && confirm('Remove this plan?')) {
                block.parentNode.removeChild(block);
            }
        }
    });
})();
</script>
