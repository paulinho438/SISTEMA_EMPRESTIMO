import Images from '../assets/images';
import {
  US,
  UK,
  Singapore,
  China,
  Netherland,
  Indonesia,
  Japan,
  France,
  Germany,
  Russia,
  Bolt,
  Users,
  Creditcard,
  Beach,
  Business,
} from '../assets/svgs';
import {moderateScale} from '../common/constant';
import {colors} from '../themes/colors';
import strings from '../i18n/strings';
import FontAwesome from 'react-native-vector-icons/FontAwesome5';
import Feather from 'react-native-vector-icons/Feather';
import AntDesign from 'react-native-vector-icons/AntDesign';
import Ionicons from 'react-native-vector-icons/Ionicons';

export const OnBoardingData = [
  {
    image: Images.OnBoarding1,
    Title: 'Finance app the safest and most trusted',
    Description:
      'Your finance work starts here. Our here to help you track and deal with speeding up your transactions.',
  },
  {
    image: Images.OnBoarding2,
    Title: 'The fastest transaction process only here',
    Description:
      'Get easy to pay all your bills with just a few steps. Paying your bills become fast and efficient.',
  },
];

export const CountriesData = [
  {
    svgIcon: <US height={moderateScale(24)} width={moderateScale(32)} />,
    title: 'US',
    FullName: 'United States',
    id: 1,
  },
  {
    svgIcon: <UK height={moderateScale(24)} width={moderateScale(32)} />,
    title: 'GB',
    FullName: 'United Kingdom',
    id: 2,
  },
  {
    svgIcon: <Singapore height={moderateScale(24)} width={moderateScale(32)} />,
    title: 'SG',
    FullName: 'Singapore',
    id: 3,
  },
  {
    svgIcon: <China height={moderateScale(24)} width={moderateScale(32)} />,
    title: 'CN',
    FullName: 'China',
    id: 4,
  },
  {
    svgIcon: (
      <Netherland height={moderateScale(24)} width={moderateScale(32)} />
    ),
    title: 'NL',
    FullName: 'Netherland',
    id: 5,
  },
  {
    svgIcon: <Indonesia height={moderateScale(24)} width={moderateScale(32)} />,
    title: 'ID',
    FullName: 'Indonesia',
    id: 6,
  },
];

export const HomeData = [
  {
    image: Images.Deposit2,
    name: 'Fabio Martins',
    subName: 'Pendente',
    dollars: 'R$ 89,90',
    color: colors.red,
  },
  {
    image: Images.Deposit2,
    name: 'Juliano Sickeira',
    subName: 'Pendente',
    dollars: 'R$ 105,20',
    color: colors.red,
  },
  {
    image: Images.Deposit2,
    name: 'Roberto justos',
    subName: 'Pendente',
    dollars: 'R$ 108,87',
    color: colors.red,
  },
  {
    image: Images.Deposit2,
    name: 'Larissa Jortem',
    subName: 'Pendente',
    dollars: 'R$ 44,27',
    color: colors.red,
  },
  {
    image: Images.Deposit2,
    name: 'Eric Lobo',
    subName: 'Pendente',
    dollars: 'R$ 28,33',
    color: colors.red,
  },
];

export const ContactsData = [
  {
    image: Images.Girl,
    name: strings.GirlName,
    id: 1,
  },
  {
    image: Images.Boy,
    name: strings.BoyName,
    id: 2,
  },
  {
    image: Images.Girl,
    name: strings.Girl2name,
    id: 3,
  },
];

export const DollarsData = [strings.$1000, strings.$2000, strings.$3000];

export const percentageData = [
  strings.per10,
  strings.per50,
  strings.per70,
  strings.per100,
];

export const TodayData = [
  {
    image: Images.UiKit,
    mainName: strings.BulkPayUi,
    subName: strings.UiNet,
    payments: strings.NineNine,
    color: colors.black,
  },
  {
    image: Images.Gym,
    mainName: strings.Gym,
    subName: strings.Payment,
    payments: strings.FourFive,
    color: colors.black,
  },
  {
    image: Images.Deposit2,
    mainName: strings.BOA,
    subName: strings.Deposit,
    payments: strings.OneThreeTwoEight,
    color: colors.numbersColor,
  },
];

export const YesterdayData = [
  {
    image: Images.BitCoin,
    mainName: strings.Bitcoin,
    subName: strings.Deposit,
    payments: strings.TwoFiveFIveZero,
    color: colors.black,
  },
  {
    image: Images.Deposit2,
    mainName: strings.BOA,
    subName: strings.Deposit,
    payments: strings.OneThreeTwoEight,
    color: colors.numbersColor,
  },
  {
    image: Images.Gym,
    mainName: strings.Gym,
    subName: strings.Payment,
    payments: strings.FourFive,
    color: colors.black,
  },
];

export const SpotifyData = [
  {
    image: Images.Spotify,
    mainName: strings.SpotifyPremium,
    subName: strings.Payment,
    payments: strings.TwoFour,
    date: strings.Dec28,
  },
  {
    image: Images.Spotify,
    mainName: strings.SpotifyPremium,
    subName: strings.Payment,
    payments: strings.OneTwoFour,
    date: strings.Nov28,
  },
  {
    image: Images.Spotify,
    mainName: strings.SpotifyPremium,
    subName: strings.Payment,
    payments: strings.fiveFour,
    date: strings.Oct28,
  },
  {
    image: Images.Spotify,
    mainName: strings.SpotifyPremium,
    subName: strings.Payment,
    payments: strings.TwoFour,
    date: strings.Dec28,
  },
  {
    image: Images.Spotify,
    mainName: strings.SpotifyPremium,
    subName: strings.Payment,
    payments: strings.OneTwoFour,
    date: strings.Nov28,
  },
];

export const miniCardDetails = [
  {
    name: strings.BulkPayCards,
    number: strings.AnnaNumber,
    image: Images.twoRound,
    backgroundColor: colors.Primary,
    color: colors.white,
  },
  {
    name: strings.BulkPayCards,
    number: strings.AnnaNumber,
    image: Images.twoRound,
    backgroundColor: colors.black,
    color: colors.white,
  },
  {
    name: strings.BulkPayCards,
    number: strings.AnnaNumber,
    image: Images.twoRound,
    backgroundColor: colors.Primary,
    color: colors.white,
  },
];

export const switchData = [strings.Personal, strings.Manage, strings.Detail];

export const ManageData = [
  {
    id: 1,
    image: Images.PhysicalCard,
    name: 'Freeze physical card',
    selected: false,
  },
  {
    id: 2,
    image: Images.Contactless,
    name: 'Disable contactless',
    selected: false,
  },
  {
    id: 3,
    image: Images.MagStripe,
    name: 'Disable magstripe',
    selected: false,
  },
];

export const BankList = [
  {
    id: 0,
    name: 'Bank Of America',
    description: 'Anabella Angela',
    card: [
      {
        id: 0,
        image: Images.card1,
      },
      {
        id: 1,
        image: Images.card3,
      },
      {
        id: 2,
        image: Images.card2,
      },
    ],
  },
  {
    id: 0,
    name: 'U.S. Bank',
    description: 'Anabella Angela',
    card: [
      {
        id: 0,
        image: Images.card1,
      },
      {
        id: 1,
        image: Images.card2,
      },
    ],
  },
  {
    id: 0,
    name: 'U.S. Bank',
    description: 'Anabella Angela',
    card: [
      {
        id: 0,
        image: Images.card1,
      },
    ],
  },
];

export const LanguageData = [
  {
    id: 1,
    svgIcon: <US height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.EnglishUs,
  },
  {
    id: 2,
    svgIcon: <UK height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.EnglishENG,
  },
  {
    id: 3,
    svgIcon: <Indonesia height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.Indonesian,
  },
  {
    id: 4,
    svgIcon: <Russia height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.Russia,
  },
  {
    id: 5,
    svgIcon: <France height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.French,
  },
  {
    id: 6,
    svgIcon: <China height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.Chinese,
  },
  {
    id: 7,
    svgIcon: <Japan height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.Japanese,
  },
  {
    id: 8,
    svgIcon: <Germany height={moderateScale(15)} width={moderateScale(20)} />,
    name: strings.Germany,
  },
  {
    id: 9,
    svgIcon: (
      <Netherland height={moderateScale(15)} width={moderateScale(20)} />
    ),
    name: strings.Netherland,
  },
];

export const contactList = [
  {
    id: 1,
    image: Images.Girl,
    name: strings.MariaSev,
    number: strings.MariaNum,
  },
  {
    id: 2,
    image: Images.Boy,
    name: strings.AndAlex,
    number: strings.AndNum,
  },
  {
    id: 3,
    image: Images.avatar1,
    name: strings.MitchellPri,
    number: strings.MitchellNum,
  },
  {
    id: 4,
    image: Images.avatar2,
    name: strings.GladysChou,
    number: strings.GladysNum,
  },
  {
    id: 5,
    image: Images.avatar3,
    name: strings.MitchellAngela,
    number: strings.AngelaNum,
  },
  {
    id: 6,
    image: Images.avatar4,
    name: strings.XiaChangLing,
    number: strings.XiaNum,
  },
];

export const NotificationData1 = [
  {
    id: 1,
    image: Images.Rewards,
    name: strings.Rewards,
    subName: strings.RewardsLine,
    Time: strings.RewardsTime,
  },
  {
    id: 2,
    image: Images.MoneyTransfer,
    name: strings.MoneyTransfer,
    subName: strings.MoneyTransferLine,
    Time: strings.MoneyTransTime,
  },
];

export const NotificationData2 = [
  {
    id: 1,
    image: Images.PaymentNotification,
    name: strings.PaymentNot,
    subName: strings.SuccessPaid,
    Time: strings.Dec23,
  },
  {
    id: 2,
    image: Images.Deposit2,
    name: strings.TopUp,
    subName: strings.SuccessTopUp,
    Time: strings.Dec20,
  },
  {
    id: 3,
    image: Images.MoneyTransfer,
    name: strings.MoneyTransfer,
    subName: strings.SuccessSent,
    Time: strings.Dec20,
  },
  {
    id: 4,
    image: Images.Cashback,
    name: strings.Cashback25,
    subName: strings.SuccessSent,
    Time: strings.Dec20,
  },
  {
    id: 5,
    image: Images.PaymentNotification,
    name: strings.PaymentNot,
    subName: strings.SuccessPaid,
    Time: strings.Dec19,
  },
];

export const TimeData = [
  {
    id: 1,
    name: strings.Day,
  },
  {
    id: 2,
    name: strings.Week,
  },
  {
    id: 3,
    name: strings.Month,
  },
  {
    id: 4,
    name: strings.Year,
  },
];

export const ChatData = [
  {
    id: 1,
    message: strings.Chat1,
    type: 'receiver',
  },
  {
    id: 2,
    message: strings.Chat2,
    type: 'sender',
  },
  {
    id: 3,
    message: strings.Chat3,
    type: 'receiver',
  },
  {
    id: 4,
    message: strings.Chat4,
    type: 'sender',
  },
];

export const LocationData = [
  {
    id: 1,
    image: Images.Star,
    reviews: '3 finalizados',
    color: colors.tabColor,
  },
  {
    id: 2,
    image: Images.MiniCar,
    reviews: '2 KM',
    color: colors.tabColor,
  },
  {
    id: 3,
    image: Images.Time,
    reviews: '2 pendentes',
    color: colors.tabColor,
  },
];

export const TransferData = [
  {
    id: 1,
    image: Images.BankAmerica,
    name: strings.BOA,
    num: strings.BankNum,
  },
  {
    id: 2,
    image: Images.USBank,
    name: strings.USBank,
    num: strings.USBankNum,
  },
];

export const TransferData2 = [
  {
    id: 4,
    image: Images.PayPal,
    name: strings.Paypal,
    num: strings.EasyPayment,
  },
  {
    id: 5,
    image: Images.AppleRound,
    name: strings.ApplePay,
    num: strings.EasyPayment,
  },
  {
    id: 6,
    image: Images.GoogleRound,
    name: strings.GooglePay,
    num: strings.EasyPayment,
  },
];

export const moneyData = [strings.$5, strings.$10, strings.$15, strings.$20];

export const moneyData2 = [
  strings.$50,
  strings.$100,
  strings.$200,
  strings.$500,
];

export const ReasonsData = [
  {
    id: 1,
    svgIcon: <FontAwesome name={'chart-pie'} size={28} />,
    name: strings.SpendDaily,
    selected: false,
  },
  {
    id: 2,
    svgIcon: <FontAwesome name={'bolt'} size={28} />,
    name: strings.FastTrans,
    selected: false,
  },
  {
    id: 3,
    svgIcon: <Feather name={'users'} size={28} />,
    name: strings.PaymentFriends,
    selected: false,
  },
  {
    id: 4,
    svgIcon: <AntDesign name={'creditcard'} size={28} />,
    name: strings.OnlinePayment,
    selected: false,
  },
  {
    id: 5,
    svgIcon: <FontAwesome name={'umbrella-beach'} size={28} />,
    name: strings.SpendTravel,
    selected: false,
  },
  {
    id: 6,
    svgIcon: <Ionicons name={'business-outline'} size={28} />,
    name: strings.FinancialAsset,
    selected: false,
  },
];

export const CurrencyList = [
  {label: 'Segunda a Sexta', value: '1'},
  {label: 'Segunda a Sàbado', value: '2'},
  {label: 'Segunda a Domingo', value: '3'},
];

export const ListClient = [
  {label: 'Todos os Status', value: '1'},
  {label: 'Vermelho (mais de 5 dias)', value: '2'},
  {label: 'Amarelo (entre 1 e 5 dias)', value: '3'},
  {label: 'Verde (1 dia)', value: '4'},
  {label: 'Azul (Sem atraso)', value: '5'}

];

export const MeSectionData = [
  {label: 'Student', value: 'Student'},
  {label: 'BusinessMan', value: 'BusinessMan'},
  {label: 'Professional', value: 'Professional'},
  {label: 'Senior Citizen', value: 'Senior Citizen'},
];



const ChartExample = () => {
  
  // Dados para o PieChart com valores dinâmicos
  const pieData = [
    { key: 1, value: data.verde, svg: { fill: '#4CAF50' } },  // Verde
    { key: 2, value: data.amarelo, svg: { fill: '#FFC107' } }, // Amarelo
    { key: 3, value: data.vermelho, svg: { fill: '#F34646' } }, // Vermelho
  ];
  

  // Função para adicionar labels de valor no PieChart
  const Labels = ({ slices }) => {
    return slices.map((slice, index) => {
      const { labelCentroid, data } = slice;
      return (
        <SVGText
          key={index}
          x={labelCentroid[0]}
          y={labelCentroid[1]}
          fill="white"
          fontSize={14}
          fontWeight="bold"
          textAnchor="middle"
          alignmentBaseline="middle"
        >
          {data.value}
        </SVGText>
      );
    });
  };

  return (
    <View style={styles2.container}>
      {/* Título */}
      <Text style={styles2.title}>Status de Atrasos 📆</Text>

      {/* Legenda */}
      <View style={styles2.legend}>
        <Text style={[styles2.legendItem, { color: '#4CAF50' }]}>
          ● Verde: 1 dia de atraso
        </Text>
        <Text style={[styles2.legendItem, { color: '#FFC107' }]}>
          ● Amarelo: entre 1 e 5 dias de atraso
        </Text>
        <Text style={[styles2.legendItem, { color: '#F34646' }]}>
          ● Vermelho: mais de 5 dias de atraso
        </Text>
      </View>

      {/* Gráfico de Anel (PieChart) */}
      <PieChart
        style={{ height: 150, width: 200, marginBottom: 16 }}
        data={pieData}
        innerRadius="70%"
        outerRadius="100%"
      >
        <Labels />
      </PieChart>
    </View>
  );
};


