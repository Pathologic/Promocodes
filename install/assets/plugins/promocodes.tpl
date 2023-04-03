/**
 * Promocodes
 * 
 * plugin to use promocodes 
 *
 * @category 	plugin
 * @version 	2.0.0
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic (m@xim.name)
 * @internal	@properties &pattern=Default promocode pattern;text;[A-Z0-9]{10} &discountTitle=Discount title;text;@CODE:Скидка по промокоду [+promocode+]; &categoryTemplates=Category templates IDs;text; &productTemplates=Products templates IDs;text
 * @internal	@events OnPageNotFound,OnCommerceInitialized,OnBeforeOrderProcessing,OnCollectSubtotals,OnOrderSaved
 * @internal    @installset base
 */

require MODX_BASE_PATH.'assets/modules/promocodes/plugin.promocodes.php';
