<?php

/**
 *      This file is part of SuiNiPai.
 *      (author) thinfell <thinfell@qq.com>
 *		[SuiNiPai] Copyright (c) 2016 Qurui Inc. Code released under the MIT License.
 *      www.suinipai.com
 */

	include  template("common/header");
?>

<div id="pt" class="bm cl">
	<div class="z">
		<a href="./" class="nvhm" title="<?php echo lang('homepage');?>">
			<?php echo $_G[setting][bbname];?>
		</a>
		<em>&raquo;</em>
		<a href="forum.php">
			<?php echo $_G[setting][navs][2][navname];?>
		</a>
		<em>&raquo;</em>
			<?php echo $navtitle;?>
	</div>
</div>
<link href="source/plugin/yinxingfei_recharge/assets/css/main.css?<?php echo VERHASH;?>" rel="stylesheet">
<div class="snp-box-main">
	<form method="post" name="pay_form" id="pay_form" autocomplete="off" onsubmit="credit_submit();return false;">
		<input type="hidden" name="formhash" value="<?php echo FORMHASH;?>">
		<input type="hidden" name="snpSubmit" value="true">
		<input type="hidden" name="snpExtcredits" id="snp-extcredits" value="0">
		<input type="hidden" id="snp-type" name="snp-type" value="<?php echo $set['type'];?>">
		<input type="hidden" id="snp-signtype" name="snp-signtype" value="<?php echo $set['signtype'];?>">
		<input type="hidden" id="snp-alipay" name="snp-alipay" value="<?php echo $set['alipay_open'];?>">
		<input type="hidden" id="snp-weixin" name="snp-weixin" value="<?php echo $set['weixin_open'];?>">
		<input type="hidden" id="snp-chinapay" name="snp-chinapay" value="0">
		<div class="snp-box-main-warp">
			<div class="snp-box-main-top">
				<div class="snp-box-main-title-step1">
					<i class="iconfont">&#xe662;</i>
					<span>选择积分</span>
				</div>
				<div class="snp-box-main-title-line">
					<i></i>
				</div>
				<div class="snp-box-main-title-step2">
					<i class="iconfont">&#xe63a;</i>
					<span>支付订单</span>
				</div>
				<div class="snp-box-main-title-line">
					<i></i>
				</div>
				<div class="snp-box-main-title-step3">
					<i class="iconfont">&#xe654;</i>
					<span>积分到账</span>
				</div>
			</div>
			<div class="tpay-notice"<?php if($extcreditsCount == 1){?>style="padding-bottom:10px;"<?php };?>>
				<ul>
					<?php
						$set_txt_array = explode("\n", $set['txt']);
						foreach ($set_txt_array as $key => $value){
					?>
						<li>
							<?php echo trim($value);?>
						</li>
					<?php
						}
					?>
				</ul>
			</div>
			<div class="snp-box-main-input">
				<div class="snp-select <?php if($extcreditsCount == 1){ ?>snp-only-one<?php }?>" name="snp-select">
					<?php if($extcreditsCount == 1){?>
						<span>温馨提示：<?php echo $extcredits[$onlyone]['ratio'].$extcredits_list[$onlyone]['title'];?> / 元</span>
						<ul>
							<?php echo $li_html;?>
						</ul>
					<?php }else{?>
						<a href="javascript:;" class="snp-select-a"><font color=#a9a9a9>选择充值积分</font></a>
						<ul>
							<?php echo $li_html;?>
						</ul>
					<?php }?>
				</div>
				<div class="snp-form-group">
					<?php if ($set['type'] == 1){?>
						<input type="text" name="snpNum" class="snp-input" placeholder="输入充值积分数量" />
					<?php }else{?>
						<input type="text" name="snpFee" class="snp-input" placeholder="输入充值金额" />
					<?php }?>
				</div>
				<div>
					<p class="spn-help-block" id="snp-tip" style="display:none;">等待计算</p>
				</div>
			</div>
			<div>
				<button type="submit" id="go-pay" class="go-pay-btn">提交订单</button>
			</div>
		</div>
		<span style="display: none" id="return_pay_form"></span>
	</form>
</div>
<script src="source/plugin/yinxingfei_recharge/assets/js/jquery.min.js"></script>
<script src="source/plugin/yinxingfei_recharge/assets/js/jquery.placeholder.min.js"></script>
<script src="source/plugin/yinxingfei_recharge/assets/js/main.js?<?php echo VERHASH;?>"></script>

<?php if ($extcreditsCount == 1){?>
<script type="text/javascript">
	var jq = jQuery.noConflict();

	var firstLi = jq('.snp-only-one li').eq(0);
	textValue = firstLi.text();
	inputValue = jq(".snp-input").val();

	//赋值基本参数
	extcreditsValue = firstLi.attr('extcredits-value');
	ratioValue = firstLi.attr('ratio-value');
	leastValue = firstLi.attr('least-value');
	mostValue = firstLi.attr('most-value');
	titleValue = firstLi.attr('title-value');
	jq('#snp-extcredits').val(extcreditsValue);

</script>
<?php }?>

<script type="text/javascript" src="http://server.suinipai.com/v1/returnscripts.php?appId=<?php echo $_G['cache']['plugin']['yinxingfei_recharge']['partner'];?>"></script>

<?php
	include  template("common/footer");
?>