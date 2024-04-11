
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'tamara-gateway', {} );

const defaultLabel = __(
	'Tamara Buy Now Pay Later',
	'woo-gutenberg-products-block'
);

const label = decodeEntities( settings.title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return decodeEntities( settings.description || '' );
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * Tamara payment method config object.
 */
const TamaraGateway = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGateway );

/** Single Checkout */
const paymentOptionTitleSingleCheckout = "Split in 4 payments without interest or hidden fees. قسم فاتورتك على 4 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelSingleCheckout = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitleSingleCheckout } />;
};
/**
 * Content component
 */
const ContentSingleCheckout = () => {
	return decodeEntities( paymentOptionTitleSingleCheckout );
};
const TamaraGatewaySingleCheckout = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-single-checkout",
	label: <LabelSingleCheckout />,
	content: <ContentSingleCheckout />,
	edit: <ContentSingleCheckout />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitleSingleCheckout,
	supports: {
		features: settings.supports,
	},
};
registerPaymentMethod( TamaraGatewaySingleCheckout );

/** Pay Next Month */
const paymentOptionTitlePayNextMonth = "Pay Next Month. ادفع الشهر الجاي";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayNextMonth = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayNextMonth } />;
};
/**
 * Content component
 */
const ContentPayNextMonth = () => {
	return decodeEntities( paymentOptionTitle );
};
const TamaraGatewayPayNextMonth = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-next-month",
	label: <LabelPayNextMonth />,
	content: <ContentPayNextMonth />,
	edit: <ContentPayNextMonth />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayNextMonth,
	supports: {
		features: settings.supports,
	},
};
registerPaymentMethod( TamaraGatewayPayNextMonth );

/** Pay Now */
const paymentOptionTitlePayNow = "Pay in full - safe, quick & hassle-free payments. تمارا: ادفعها كاملة - وسيلة دفع آمنة وسهلة.";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayNow = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayNow } />;
};
/**
 * Content component
 */
const ContentPayNow = () => {
	return decodeEntities( paymentOptionTitlePayNow );
};
const TamaraGatewayPayNow = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-now",
	label: <LabelPayNow />,
	content: <ContentPayNow />,
	edit: <ContentPayNow />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayNow,
	supports: {
		features: settings.supports,
	},
};
registerPaymentMethod( TamaraGatewayPayNow );

/** Pay In 2 */
var paymentOptionTitlePayIn2 = "Split in 2 payments without interest or hidden fees. قسم فاتورتك على 2 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
var LabelPayIn2 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn2 } />;
};
/**
 * Content component
 */
var ContentPayIn2 = () => {
	return decodeEntities( paymentOptionTitlePayIn2 );
};
const TamaraGatewayPayIn2 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-2",
	label: <LabelPayIn2 />,
	content: <ContentPayIn2 />,
	edit: <ContentPayIn2 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn2,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn2 );

/** Pay In 3 */
var paymentOptionTitlePayIn3 = "Split in 3 payments without interest or hidden fees. قسم فاتورتك على 3 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
var LabelPayIn3 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn3 } />;
};
/**
 * Content component
 */
var ContentPayIn3 = () => {
	return decodeEntities( paymentOptionTitlePayIn3 );
};
const TamaraGatewayPayIn3 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-3",
	label: <LabelPayIn3 />,
	content: <ContentPayIn3 />,
	edit: <ContentPayIn3 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn3,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn3 );

/** Pay In 4 */
var paymentOptionTitlePayIn4 = "Split in 4 payments without interest or hidden fees. قسم فاتورتك على 4 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
var LabelPayIn4 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn4 } />;
};
/**
 * Content component
 */
var ContentPayIn4 = () => {
	return decodeEntities( paymentOptionTitlePayIn4 );
};
const TamaraGatewayPayIn4 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-4",
	label: <LabelPayIn4 />,
	content: <ContentPayIn4 />,
	edit: <ContentPayIn4 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn4,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn4 );

/** Pay In 5 */
const paymentOptionTitlePayIn5 = "Split in 5 payments without interest or hidden fees. قسم فاتورتك على 5 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn5 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn5 } />;
};
/**
 * Content component
 */
const ContentPayIn5 = () => {
	return decodeEntities( paymentOptionTitlePayIn5 );
};
const TamaraGatewayPayIn5 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-5",
	label: <LabelPayIn5 />,
	content: <ContentPayIn5 />,
	edit: <ContentPayIn5 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn5,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn5 );

/** Pay In 6 */
const paymentOptionTitlePayIn6 = "Split in 6 payments without interest or hidden fees. قسم فاتورتك على 6 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn6 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn6 } />;
};
/**
 * Content component
 */
const ContentPayIn6 = () => {
	return decodeEntities( paymentOptionTitlePayIn6 );
};
const TamaraGatewayPayIn6 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-6",
	label: <LabelPayIn6 />,
	content: <ContentPayIn6 />,
	edit: <ContentPayIn6 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn6,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn6 );

/** Pay In 7 */
const paymentOptionTitlePayIn7 = "Split in 7 payments without interest or hidden fees. قسم فاتورتك على 7 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn7 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn7 } />;
};
/**
 * Content component
 */
const ContentPayIn7 = () => {
	return decodeEntities( paymentOptionTitlePayIn7 );
};
const TamaraGatewayPayIn7 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-7",
	label: <LabelPayIn7 />,
	content: <ContentPayIn7 />,
	edit: <ContentPayIn7 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn7,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn7 );

/** Pay In 8 */
const paymentOptionTitlePayIn8 = "Split in 8 payments without interest or hidden fees. قسم فاتورتك على 8 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn8 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn8 } />;
};
/**
 * Content component
 */
const ContentPayIn8 = () => {
	return decodeEntities( paymentOptionTitlePayIn8 );
};
const TamaraGatewayPayIn8 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-8",
	label: <LabelPayIn8 />,
	content: <ContentPayIn8 />,
	edit: <ContentPayIn8 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn8,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn8 );

/** Pay In 9 */
const paymentOptionTitlePayIn9 = "Split in 9 payments without interest or hidden fees. قسم فاتورتك على 9 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn9 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn9 } />;
};
/**
 * Content component
 */
const ContentPayIn9 = () => {
	return decodeEntities( paymentOptionTitlePayIn9 );
};
const TamaraGatewayPayIn9 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-9",
	label: <LabelPayIn9 />,
	content: <ContentPayIn9 />,
	edit: <ContentPayIn9 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn9,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn9 );

/** Pay In 10 */
const paymentOptionTitlePayIn10 = "Split in 10 payments without interest or hidden fees. قسم فاتورتك على 10 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn10 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn10 } />;
};
/**
 * Content component
 */
const ContentPayIn10 = () => {
	return decodeEntities( paymentOptionTitlePayIn10 );
};
const TamaraGatewayPayIn10 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-10",
	label: <LabelPayIn10 />,
	content: <ContentPayIn10 />,
	edit: <ContentPayIn10 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn10,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn10 );

/** Pay In 11 */
const paymentOptionTitlePayIn11 = "Split in 11 payments without interest or hidden fees. قسم فاتورتك على 11 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn11 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn11 } />;
};
/**
 * Content component
 */
const ContentPayIn11 = () => {
	return decodeEntities( paymentOptionTitlePayIn11 );
};
const TamaraGatewayPayIn11 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-11",
	label: <LabelPayIn11 />,
	content: <ContentPayIn11 />,
	edit: <ContentPayIn11 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn11,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn11 );

/** Pay In 12 */
const paymentOptionTitlePayIn12 = "Split in 12 payments without interest or hidden fees. قسم فاتورتك على 12 دفعات بدون فوائد ورسوم خفية";
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPayIn12 = ( props ) => {
   const { PaymentMethodLabel } = props.components;
   return <PaymentMethodLabel text={ paymentOptionTitlePayIn12 } />;
};
/**
 * Content component
 */
var ContentPayIn12 = () => {
	return decodeEntities( paymentOptionTitlePayIn12 );
};
const TamaraGatewayPayIn12 = {
	// The name must match the id of the payment gateway
	name: "tamara-gateway-pay-in-12",
	label: <LabelPayIn12 />,
	content: <ContentPayIn12 />,
	edit: <ContentPayIn12 />,
	canMakePayment: () => true,
	ariaLabel: paymentOptionTitlePayIn12,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TamaraGatewayPayIn12 );
